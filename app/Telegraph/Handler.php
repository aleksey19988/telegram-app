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

        $message = $this->createFormattedCommitMessage(json_decode($dataAsJson, true));

        $logController = new LogController();
        $isLoggedSuccess = $logController->saveCommit(json_decode($dataAsJson, true));

        if ($isLoggedSuccess) {
            $message = $this->addLogAttentionToMessage($message);
        }

        $chat = TelegraphChat::find(1);
        $chat->html($message)->keyboard(Keyboard::make()->buttons([
            Button::make('Статистика')->action('stats'),
        ]))->withoutPreview()->send();
    }

    private function addLogAttentionToMessage($message): string
    {
        $authorTgNickName = env('AUTHOR_S_TG_NICKNAME');
        $message .= "\n\n❗ {$authorTgNickName}, проверь почему запись не сохранилась в БД ❗";

        return $message;
    }

    public function stats(): void
    {
        $dateTime = Carbon::now()->locale('ru');
        Telegraph::message('За какой период хочешь получить статистику?')
            ->keyboard(Keyboard::make()->buttons([
                Button::make(
                    'За сегодня (' . $dateTime->isoFormat('D MMMM, dddd') . ')')
                    ->action('stats-by-period')->param('from', $dateTime->toDateString())
                    ->param('to', $dateTime->toDateString()),
                Button::make(
                    'За неделю (С ' . Carbon::now()->locale('ru')->subDays(7)->isoFormat('D MMMM') . ' по ' . $dateTime->isoFormat('D MMMM') . ')')
                    ->action('stats-by-period')
                    ->param('from', Carbon::now()->locale('ru')->subDays(7)->toDateString())->param('to', $dateTime->toDateString()),
                Button::make(
                    'За месяц (С ' . Carbon::now()->locale('ru')->subMonths(1)->isoFormat('D MMMM') . ' по ' . $dateTime->isoFormat('D MMMM') . ')')
                    ->action('stats-by-period')
                    ->param('from', Carbon::now()->locale('ru')->subMonths(1)->toDateString())->param('to', $dateTime->toDateString()),
                Button::make('Отмена')->action('reset'),
            ]))->send();
    }

    public function reset(): void
    {
        TelegraphChat::find(1)->html('Понял')->send();
    }

    private function createFormattedCommitMessage($requestData): string
    {
        [
            'ref' => $ref,
            'repository' => $repositoryData,
            'commits' => $commits
        ] = $requestData;
        $message = [];
        $commitCollection = collect($commits[0]);
        $repositoryCollection = collect($repositoryData);
        $authorName = Arr::get($commitCollection->all(), 'author.name');

        $message[] = "✅ Новый коммит от пользователя:\n{$authorName}";
        $message[] = "📋 Имя проекта:\n{$repositoryCollection->get('name')}";
        $message[] = "🌿 Ветка:\n{$ref}";
        $message[] = "🔗 Ссылка на проект:\n{$repositoryCollection->get('html_url')}";
        $message[] = "🔗 Ссылка на коммит:\n{$commitCollection->get('url')}";

        if ($commitCollection->get('modified')) {
            $changedFilesList = implode("\n", $commitCollection->get('modified'));
            $message[] = "✏️ Обновлённые файлы:\n{$changedFilesList}";
        }

        if ($commitCollection->get('added')) {
            $addedFilesList = implode("\n", $commitCollection->get('added'));
            $message[] = "➕ Созданные файлы:\n{$addedFilesList}";
        }

        if ($commitCollection->get('added')) {
            $removedFilesList = implode("\n", $commitCollection->get('removed'));
            $message[] = "➖ Удалённые файлы:\n{$removedFilesList}";
        }

        $message[] = "💬 Комментарий:\n<blockquote>{$commitCollection->get('message')}</blockquote>";

        return implode("\n\n", $message);
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->reply("Пока не знаю команду {$text->toString()}");
    }
}
