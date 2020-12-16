<?php

namespace App\Controller\EasyAdmin;

use App\Entity\Settings;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Settings::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
