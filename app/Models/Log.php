<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'log';
    protected $fillable = ['repository', 'commit'];
    public $timestamps = true;

    public function fillFields($data): void
    {
        ['repository' => $repositoryData, 'commits' => $commits] = $data;

        $this->repository = json_encode(collect($repositoryData)->all(), JSON_UNESCAPED_UNICODE);
        $this->commit = json_encode(collect($commits[0])->all(), JSON_UNESCAPED_UNICODE);
    }
}
