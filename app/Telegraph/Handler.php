<?php

namespace App\Telegraph;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    public function sendMessage(Request $request): void
    {
        ['payload' => $data] = $request->all();

        $message = $this->createFormattedMessage(json_decode($data, true));

        $chat = TelegraphChat::find(1);
        $chat->html($message)->withoutPreview()->send();
    }

    private function createFormattedMessage($requestData): string
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
