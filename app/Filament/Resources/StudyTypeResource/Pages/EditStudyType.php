<?php

namespace App\Filament\Resources\StudyTypeResource\Pages;

use App\Filament\Resources\StudyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudyType extends EditRecord
{
    protected static string $resource = StudyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
