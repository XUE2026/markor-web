<?php

namespace Markor;

interface StorageInterface
{
    public function listFiles(): array;
    public function getFile(string $filename): ?array;
    public function saveFile(string $filename, string $content): bool;
    public function deleteFile(string $filename): bool;
}
