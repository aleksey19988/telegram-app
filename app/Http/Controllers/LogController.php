<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Log $log)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Log $log)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Log $log)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Log $log)
    {
        //
    }

    public function saveCommit($data): bool
    {
        $model = new Log();
        $model->fillFields($data);

        return $model->save();
    }

    public function getInfoByPeriod(Carbon $from, Carbon $to): string
    {
        $result = [];
        $rows = Log::query()
            ->whereBetween('created_at', [$from, $to])
            ->get();
        $commitsCount = $rows->count();

        $result[] = "📋 Всего коммитов: $commitsCount";

        if ($commitsCount) {
            $neededDataForGroup = collect();
            foreach($rows as $row) {
                ['repository' => $repository, 'commit' => $commit] = $row;
                $neededDataForGroup->add([
                    'repository' => json_decode($repository, true),
                    'commit' => json_decode($commit, true),
                ]);
            }

            $groupedInfoByCommitOwner = $this->getMessageTextByGroupedData($neededDataForGroup->countBy('commit.author.name'));
            $groupedInfoByProjectName = $this->getMessageTextByGroupedData($neededDataForGroup->countBy('repository.name'));

            $result[] = "😼 Количество коммитов по сотрудникам:\n$groupedInfoByCommitOwner";
            $result[] = "🗄 Количество коммитов по проектам:\n$groupedInfoByProjectName";
        }

        return implode("\n\n", $result);
    }

    public function commits()
    {

    }

    private function getMessageTextByGroupedData($groupedData): string
    {
        $result = [];

        foreach($groupedData as $key => $value) {
            $result[] = "$key: $value";
        }

        return implode("\n", $result);
    }
}
