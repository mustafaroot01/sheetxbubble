<?php

namespace App\Filament\Resources\StudyTypeResource\Pages;

use App\Filament\Resources\StudyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudyTypes extends ListRecords
{
    protected static string $resource = StudyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
