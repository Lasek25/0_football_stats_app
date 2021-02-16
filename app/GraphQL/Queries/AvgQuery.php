<?php

namespace App\GraphQL\Queries;

// use App\Models\TeamsInCompetition;

use App\Models\TeamsInCompetition;
use App\Models\TeamsInMatch;
use App\GraphQL\Types\AvgResult;

class AvgQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args)
    {
        $results = collect();
        // month (after ending the season and before starting the new one) necessary for the correct year selection
        $month = 7;
        if (now()->month > $month) {
            $year = now()->year . '/' . now()->addYear()->year;
        }
        else {
            $year = now()->addYears(-1)->year . '/' . now()->year;
        }
        // $allTeamsInCompetition = TeamsInMatch::where('teams_in_matches.updated_at','!=',null)->join('matches', 'teams_in_matches.match_id', '=', 'matches.id')->orderBy('matches.date','desc');
        // $allTeamsInCompetition = TeamsInMatch::where('teams_in_matches.updated_at','!=',null)
        //     ->join('matches', 'teams_in_matches.match_id', '=', 'matches.id')
        //     ->join('teams_in_competitions', 'teams_in_matches.teams_in_competition_id', '=', 'teams_in_competitions.id')
        //     ->select('teams_in_matches.*','teams_in_competitions.competition_id','teams_in_competitions.season')
        //     ->where('teams_in_competitions.season','=',$year)
        //     ->orderBy('teams_in_matches.teams_in_competition_id','asc')
        //     ->latest('matches.date');
        $allTeamsInCompetition = TeamsInCompetition::where('teams_in_competitions.season','=',$year)
            ->orderBy('teams_in_competitions.id','asc');

        $args['competition'] != 0 
            ? $allTeamsInCompetition = $allTeamsInCompetition->where('teams_in_competitions.competition_id','=',$args['competition'])->get()
            : $allTeamsInCompetition = $allTeamsInCompetition->get();

        foreach($allTeamsInCompetition as $team) {
            $filteredMatches = $team->teamsInMatches->filter(function ($match) {
                return $match->updated_at != '';
            })->sortByDesc('match.date');
            $args['matchesQuantity'] != 0 
                ? $filteredMatches = $filteredMatches->take($args['matchesQuantity'])
                : '';

            $tmpTeam = new AvgResult();
            $tmpTeam->teamsInCompetition = $team;
            $goals = collect();
            $corners = collect();
            $yellowCards = collect();
            $redCards = collect();
            $fouls = collect();
            $offsides = collect();
            $shotsOnGoal = collect();

            foreach($filteredMatches as $match) {
                $goals->push($match->goals);
                $corners->push($match->corners);
                $yellowCards->push($match->yellow_cards);
                $redCards->push($match->red_cards);
                $fouls->push($match->fouls);
                $offsides->push($match->offsides);
                $shotsOnGoal->push($match->shots_on_goal);
            }
            $tmpTeam->avgGoals = round($goals->avg(), 2);
            $tmpTeam->goals = $goals;
            $tmpTeam->corners = $corners;
            $tmpTeam->yellowCards = $yellowCards;
            $tmpTeam->redCards = $redCards;
            $tmpTeam->fouls = $fouls;
            $tmpTeam->offsides = $offsides;
            $tmpTeam->shotsOnGoal = $shotsOnGoal;
            $results->push($tmpTeam);
        }
        // $all->push($allTeamsInCompetition->map(function ($team) {
        //     return $team->teamsInMatches->filter(function ($match) {
        //         return $match->updated_at != '';
        //     });
        // }));
        // foreach($all as $a) {
        //     // $g = $a->teamsInMatches->where($a->teamsInMatches()->updated_at != '')->count();
        //     $results->push($a->map(function($team){
        //         return $team->map(function ($m) {
        //             return $m->goals;
        //         });
        //     }));
        // }
        return $results->sortByDesc('avgGoals');
        // if($args['matchesQuantity'] == 0)
        //     return $allTeamsInCompetition;
        // else {
        //     $counter = 0;
        //     $tmpTeam = $allTeamsInCompetition[0]->teamsInCompetition->id;
        //     foreach($allTeamsInCompetition as $teamInMatch) {
        //         if($teamInMatch->teamsInCompetition->id != $tmpTeam) {
        //             $counter = 0;
        //             $tmpTeam = $teamInMatch->teamsInCompetition->id;
        //         }
        //         if($counter < $args['matchesQuantity']) {
        //             $results->push($teamInMatch);
        //             $counter++;
        //         }
        //     }
        //     return $results;
        // }
    }
}
