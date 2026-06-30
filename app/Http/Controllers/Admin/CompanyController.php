<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /** 公司名單列表（可搜尋）。 */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $companies = $request->user()->companies()
            ->when($q !== '', fn ($query) => $query
                ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('tax_id', 'like', "%{$q}%")))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.companies.index', ['companies' => $companies, 'q' => $q]);
    }

    /** 新增單筆公司。 */
    public function store(Request $request)
    {
        $data = $this->validateCompany($request);

        $request->user()->companies()->create($data);

        return back()->with('status', "已新增「{$data['name']}」。");
    }

    /** 更新公司。 */
    public function update(Request $request, Company $company)
    {
        $this->authorizeCompany($request, $company);

        $company->update($this->validateCompany($request, $company));

        return back()->with('status', '已更新。');
    }

    /** 刪除公司。 */
    public function destroy(Request $request, Company $company)
    {
        $this->authorizeCompany($request, $company);

        $company->delete();

        return back()->with('status', '已刪除。');
    }

    /**
     * 批次貼上匯入：每行一筆，格式「統編,公司名稱」（逗號／Tab／空白皆可分隔）。
     * 以 (主辦者, 統編) 為鍵 upsert，重複者更新名稱。
     */
    public function import(Request $request)
    {
        $data = $request->validate(['rows' => ['required', 'string', 'max:100000']]);

        $created = $updated = $skipped = 0;
        $errors = [];
        $user = $request->user();

        foreach (preg_split('/\r\n|\r|\n/', $data['rows']) as $i => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // 先以逗號/Tab 分隔，否則退回以第一段空白切出統編
            $parts = preg_split('/[,\t]+/', $line, 2);
            if (count($parts) < 2) {
                $parts = preg_split('/\s+/', $line, 2);
            }

            $taxId = Company::normalizeTaxId($parts[0] ?? '');
            $name = trim($parts[1] ?? '');

            if (! Company::isValidFormat($taxId) || $name === '') {
                $skipped++;
                if (count($errors) < 10) {
                    $errors[] = '第 '.($i + 1).' 行：'.$line;
                }
                continue;
            }

            $company = $user->companies()->firstOrNew(['tax_id' => $taxId]);
            $existed = $company->exists;
            $company->fill(['name' => mb_substr($name, 0, 191), 'active' => true])->save();
            $existed ? $updated++ : $created++;
        }

        $msg = "匯入完成：新增 {$created} 筆、更新 {$updated} 筆";
        if ($skipped) {
            $msg .= "、略過 {$skipped} 筆（格式錯誤）";
        }

        return back()->with('status', $msg)->with('import_errors', $errors);
    }

    private function validateCompany(Request $request, ?Company $company = null): array
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'tax_id' => [
                'required', 'string', 'regex:/^\d{8}$/',
                Rule::unique('companies', 'tax_id')
                    ->where(fn ($q) => $q->where('user_id', $userId))
                    ->ignore($company?->id),
            ],
            'name' => ['required', 'string', 'max:191'],
            'note' => ['nullable', 'string', 'max:191'],
            'active' => ['nullable', 'boolean'],
        ], [
            'tax_id.regex' => '統編須為 8 碼數字。',
            'tax_id.unique' => '此統編已在名單中。',
        ]);

        $data['active'] = $request->boolean('active', true);

        return $data;
    }

    private function authorizeCompany(Request $request, Company $company): void
    {
        abort_unless($company->user_id === $request->user()->id, 403);
    }
}
