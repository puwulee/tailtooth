<?php

namespace App\Services\Contracts;

interface LineProfileFetcher
{
    /**
     * 以授權碼換取使用者資料。
     * @return array{userId:string, displayName:?string, pictureUrl:?string, email:?string}
     */
    public function fetch(string $code, string $redirectUri): array;
}
