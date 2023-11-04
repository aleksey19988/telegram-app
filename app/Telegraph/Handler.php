<?php

namespace App\Telegraph;

use App\Http\Controllers\LogController;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    public function handleCommit(Request $request): void
    {
        ['payload' => $dataAsJson] = $request->all();
        $commitDataAsArr = json_decode($dataAsJson, true);

        $message = $this->createFormattedCommitMessage($commitDataAsArr);

        $logController = new LogController();
        $isLoggedSuccess = $logController->saveCommit($commitDataAsArr);

        if (!$isLoggedSuccess) {
            $message = $this->addLogAttentionToMessage($message);
        }

        $chat = TelegraphChat::query()->find(1);
        $keyboard = Keyboard::make()
            ->row([
                Button::make('Перейти к проекту')->url($this->getProjectUrl($commitDataAsArr)),
                Button::make('Перейти к коммиту')->url($this->getCommitUrl($commitDataAsArr)),
            ])
            ->row([
                Button::make('Статистика коммитов')->action('statsByCommits'),
            ]);
        $chat->html($message)->keyboard($keyboard)->send();
    }

    private function addLogAttentionToMessage($message): string
    {
        $authorTgNickName = env('AUTHOR_S_TG_NICKNAME');
        $message .= "\n\n❗ $authorTgNickName, проверь почему запись не сохранилась в БД ❗";

        return $message;
    }

    public function statsByCommits(): void
    {
        $dateTime = Carbon::now()->locale('ru');
        Telegraph::message('За какой период хочешь получить статистику?')
            ->keyboard(Keyboard::make()->buttons([
                Button::make(
                    'За сегодня (' . $dateTime->isoFormat('D MMMM, dddd') . ')')
                    ->action('statsByPeriod')->param('from', $dateTime->toDateString())
                    ->param('to', $dateTime->toDateString()),
                Button::make(
                    'За неделю (С ' . Carbon::now()->locale('ru')->subDays(7)->isoFormat('D MMMM') . ' по ' . $dateTime->isoFormat('D MMMM') . ')')
                    ->action('statsByPeriod')
                    ->param('from', Carbon::now()->locale('ru')->subDays(7)->toDateString())->param('to', $dateTime->toDateString()),
                Button::make(
                    'За месяц (С ' . Carbon::now()->locale('ru')->subMonths()->isoFormat('D MMMM') . ' по ' . $dateTime->isoFormat('D MMMM') . ')')
                    ->action('statsByPeriod')
                    ->param('from', Carbon::now()->locale('ru')->subMonths()->toDateString())->param('to', $dateTime->toDateString()),
                Button::make('Отмена')->action('reset'),
            ]))->send();
    }

    public function reset(): void
    {
        $this->chat->html('Отмена, понял')->send();
    }

    public function statsByPeriod(): void
    {
        $this->chat->html("Выбран период с {$this->data->get('from')} по {$this->data->get('to')}")->send();

        $from = Carbon::createFromFormat('Y-m-d', $this->data->get('from'))->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $this->data->get('to'))->endOfDay();

        $logController = new LogController();
        $messageData = $logController->getInfoByPeriod($from, $to);

        $dateTime = Carbon::now()->locale('ru');

        $this->chat->html($messageData)->keyboard(Keyboard::make()->buttons([
            Button::make(
                'За сегодня (' . $dateTime->isoFormat('D MMMM, dddd') . ')')
                ->action('statsByPeriod')->param('from', $dateTime->toDateString())
                ->param('to', $dateTime->toDateString()),
            Button::make(
                'За неделю (С ' . Carbon::now()->locale('ru')->subDays(7)->isoFormat('D MMMM') . ' по ' . $dateTime->isoFormat('D MMMM') . ')')
                ->action('statsByPeriod')
                ->param('from', Carbon::now()->locale('ru')->subDays(7)->toDateString())->param('to', $dateTime->toDateString()),
            Button::make(
                'За месяц (С ' . Carbon::now()->locale('ru')->subMonths()->isoFormat('D MMMM') . ' по ' . $dateTime->isoFormat('D MMMM') . ')')
                ->action('statsByPeriod')
                ->param('from', Carbon::now()->locale('ru')->subMonths()->toDateString())->param('to', $dateTime->toDateString()),
            Button::make('Отмена')->action('reset'),
        ]))->send();
    }

    private function createFormattedCommitMessage($data): string
    {
        [
            'ref' => $ref,
            'repository' => $repository,
            'commits' => $commits
        ] = $data;
        $message = [];
        $commitCollection = collect($commits[0]);
        $repositoryCollection = collect($repository);
        $authorName = Arr::get($commitCollection->all(), 'author.name');

        $message[] = "✅ Новый коммит от пользователя:\n$authorName";
        $message[] = "📋 Имя проекта:\n{$repositoryCollection->get('name')}";
        $message[] = "🌿 Ветка:\n$ref";
        $message[] = "💬 Комментарий:\n<blockquote>{$commitCollection->get('message')}</blockquote>";

        return implode("\n\n", $message);
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->reply("Пока не знаю команду {$text->toString()}");
    }

    private function getProjectUrl($data): string
    {
        ['repository' => $repository] = $data;

        return collect($repository)->get('html_url');
    }

    private function getCommitUrl($data): string
    {
        ['commits' => $commits] = $data;

        return collect($commits[0])->get('url');
    }
}
