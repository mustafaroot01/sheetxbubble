<?php

namespace App\Filament\Resources;

use App\Filament\Pages\CorrectSheet as CorrectionPage;
use App\Filament\Pages\GenerateSheets;
use App\Filament\Resources\ExamResource\Pages;
use App\Models\Exam;
use App\Models\TeacherAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.exams');
    }

    public static function getModelLabel(): string
    {
        return __('app.exam.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.exam.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isTeacher = $user && $user->hasRole('teacher');

        return $form->schema([
            Forms\Components\Section::make(__('app.exam.exam_info'))
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('stage_id')
                            ->label(__('app.exam.stage'))
                            ->required()
                            ->native(true)
                            ->options(function () use ($user, $isTeacher) {
                                if ($isTeacher) {
                                    return TeacherAssignment::where('teacher_id', $user->id)
                                        ->with('stage')
                                        ->get()
                                        ->pluck('stage.name', 'stage_id')
                                        ->unique();
                                }
                                return \App\Models\Stage::pluck('name', 'id');
                            })
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('subject_id', null)),
                        Forms\Components\Select::make('subject_id')
                            ->label(__('app.exam.subject'))
                            ->required()
                            ->native(true)
                            ->options(function (Get $get) use ($user, $isTeacher) {
                                $stageId = $get('stage_id');
                                if (!$stageId) return [];
                                if ($isTeacher) {
                                    return TeacherAssignment::where('teacher_id', $user->id)
                                        ->where('stage_id', $stageId)
                                        ->with('subject')
                                        ->get()
                                        ->pluck('subject.name', 'subject_id')
                                        ->unique();
                                }
                                $stage = \App\Models\Stage::find($stageId);
                                if ($stage) {
                                    return $stage->subjects()->pluck('subjects.name', 'subjects.id');
                                }
                                return \App\Models\Subject::pluck('name', 'id');
                            })
                            ->live()
                            ->placeholder(fn (Get $get) => !$get('stage_id') ? __('app.exam.select_stage_first') : __('app.exam.select')),
                        Forms\Components\Select::make('study_type_id')
                            ->label(__('app.exam.study_type'))
                            ->required()
                            ->native(true)
                            ->options(\App\Models\StudyType::pluck('name', 'id')),
                    ]),
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\DatePicker::make('exam_date')
                            ->label(__('app.exam.exam_date'))
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                        Forms\Components\Select::make('status')
                            ->label(__('app.exam.status'))
                            ->native(true)
                            ->options([
                                'open' => __('app.exam.status_open'),
                                'closed' => __('app.exam.status_closed'),
                            ])
                            ->default('open')
                            ->required(),
                    ]),
                    Forms\Components\Hidden::make('teacher_id')
                        ->default(fn () => auth()->id()),
                ]),

            Forms\Components\Section::make(__('app.exam.marks_questions'))
                ->icon('heroicon-o-calculator')
                ->schema([
                    Forms\Components\Grid::make(5)->schema([
                        Forms\Components\TextInput::make('total_mark')
                            ->label(__('app.exam.total_mark'))
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $total = $get('total_mark');
                                $count = $get('question_count');
                                if ($total && $count && $count > 0) {
                                    $set('mark_per_question', round($total / $count, 2));
                                }
                            }),
                        Forms\Components\TextInput::make('question_count')
                            ->label(__('app.exam.question_count'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $total = $get('total_mark');
                                $count = (int) $get('question_count');
                                if ($total && $count > 0) {
                                    $set('mark_per_question', round($total / $count, 2));
                                }
                            }),
                        Forms\Components\TextInput::make('mark_per_question')
                            ->label(__('app.exam.mark_per_question'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('choices_count')
                            ->label(__('app.exam.choices_count'))
                            ->required()
                            ->numeric()
                            ->default(4)
                            ->minValue(2)
                            ->maxValue(6)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('pass_mark')
                            ->label(__('app.exam.pass_mark'))
                            ->required()
                            ->numeric()
                            ->default(50)
                            ->suffix('%'),
                    ]),
                ]),

            Forms\Components\Section::make(__('app.exam.model_answer'))
                ->icon('heroicon-o-key')
                ->description(__('app.exam.model_answer_desc'))
                ->schema(function (Get $get) {
                    $count = (int) ($get('question_count') ?: 0);
                    $choices = (int) ($get('choices_count') ?: 4);
                    $labels = array_slice(['A','B','C','D','E','F'], 0, $choices);
                    $options = array_combine($labels, $labels);

                    if ($count === 0) {
                        return [
                            Forms\Components\Placeholder::make('no_questions')
                                ->label('')
                                ->content(__('app.exam.set_question_count_first')),
                        ];
                    }

                    $fields = [];
                    for ($i = 0; $i < $count; $i++) {
                        $fields[] = Forms\Components\Select::make("model_answer.{$i}")
                            ->label(__('app.exam.q_num', ['num' => $i + 1]))
                            ->options($options)
                            ->required()
                            ->native(true);
                    }

                    return [
                        Forms\Components\Grid::make(6)->schema($fields),
                    ];
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')->label(__('app.exam.subject'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stage.name')->label(__('app.exam.stage'))->sortable(),
                Tables\Columns\TextColumn::make('studyType.name')->label(__('app.exam.study_type'))->sortable(),
                Tables\Columns\TextColumn::make('exam_date')->label(__('app.exam.exam_date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('total_mark')->label(__('app.exam.total_mark'))->sortable(),
                Tables\Columns\TextColumn::make('question_count')->label(__('app.exam.question_count'))->sortable(),
                Tables\Columns\TextColumn::make('results_count')->counts('results')->label(__('app.exam.corrected')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.exam.status'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'open' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'open' ? __('app.exam.status_open') : __('app.exam.status_closed'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')->label(__('app.exam.teacher'))->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label(__('app.exam.subject')),
                Tables\Filters\SelectFilter::make('stage_id')
                    ->relationship('stage', 'name')
                    ->label(__('app.exam.stage')),
                Tables\Filters\SelectFilter::make('study_type_id')
                    ->relationship('studyType', 'name')
                    ->label(__('app.exam.study_type')),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.exam.status'))
                    ->options([
                        'open' => __('app.exam.status_open'),
                        'closed' => __('app.exam.status_closed'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('print_sheets')
                    ->label(__('app.exam.print_sheets'))
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Exam $record) => GenerateSheets::getUrl(['exam_id' => $record->id]))
                    ->visible(fn (Exam $record) => $record->status === 'open'),
                Tables\Actions\Action::make('correct')
                    ->label(__('app.exam.correct_sheets'))
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->url(fn (Exam $record) => CorrectionPage::getUrl(['exam_id' => $record->id]))
                    ->visible(fn (Exam $record) => $record->status === 'open'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn (Exam $record) => $record->status === 'open' ? __('app.exam.close_exam') : __('app.exam.open_exam'))
                        ->icon(fn (Exam $record) => $record->status === 'open' ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                        ->color(fn (Exam $record) => $record->status === 'open' ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(fn (Exam $record) => $record->update(['status' => $record->status === 'open' ? 'closed' : 'open'])),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning'),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip(__('app.actions')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
