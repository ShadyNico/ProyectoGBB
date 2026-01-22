<?php

class GuideController
{
    public function show(): void
    {
        $title = 'DistributedShop - Guía Completa';
        require __DIR__ . '/../views/guide.php';
    }
}
