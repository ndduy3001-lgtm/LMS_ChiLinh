<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_xp\output;

use renderable;
use templatable;
use renderer_base;

/**
 * Renderable for the full leaderboard page.
 *
 * Prepares leaderboard data for the Mustache template.
 *
 * @package    local_xp
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class leaderboard_page implements renderable, templatable {

    /** @var array Leaderboard entries. */
    protected array $entries;

    /** @var int Total count of entries. */
    protected int $totalcount;

    /** @var int Current user's rank. */
    protected int $userrank;

    /** @var object|false Current user's XP data. */
    protected $userxp;

    /** @var int Course ID. */
    protected int $courseid;

    /** @var int Current page. */
    protected int $page;

    /** @var int Per page. */
    protected int $perpage;

    /** @var int Current user ID. */
    protected int $currentuserid;

    /**
     * Constructor.
     *
     * @param array $entries Leaderboard entries.
     * @param int $totalcount Total number of entries.
     * @param int $userrank Current user's rank.
     * @param object|false $userxp Current user's XP data.
     * @param int $courseid Course ID.
     * @param int $page Current page.
     * @param int $perpage Items per page.
     * @param int $currentuserid Current user ID.
     */
    public function __construct(
        array $entries,
        int $totalcount,
        int $userrank,
        $userxp,
        int $courseid,
        int $page,
        int $perpage,
        int $currentuserid
    ) {
        $this->entries = $entries;
        $this->totalcount = $totalcount;
        $this->userrank = $userrank;
        $this->userxp = $userxp;
        $this->courseid = $courseid;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->currentuserid = $currentuserid;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output The renderer.
     * @return array Template data.
     */
    public function export_for_template(renderer_base $output): array {
        global $PAGE;

        $levelcalc = new \local_xp\level_calculator();
        $entries = [];

        foreach ($this->entries as $entry) {
            $userpicture = new \user_picture((object) [
                'id' => $entry->userid,
                'picture' => $entry->picture ?? 0,
                'firstname' => $entry->firstname,
                'lastname' => $entry->lastname,
                'email' => $entry->email ?? '',
                'imagealt' => $entry->imagealt ?? '',
                'firstnamephonetic' => $entry->firstnamephonetic ?? '',
                'lastnamephonetic' => $entry->lastnamephonetic ?? '',
                'middlename' => $entry->middlename ?? '',
                'alternatename' => $entry->alternatename ?? '',
            ]);
            $userpicture->size = 40;

            $points = (int) $entry->points;
            $level = (int) $entry->level;
            $progress = $levelcalc->get_level_progress($points);
            $xptonext = $levelcalc->get_xp_for_next_level($level);
            $remaining = $levelcalc->get_level_threshold($level + 1) - $points;
            $ismaxlevel = ($level >= $levelcalc->get_max_level());

            $entries[] = [
                'rank' => $entry->rank,
                'userid' => $entry->userid,
                'fullname' => fullname($entry),
                'userpictureurl' => $userpicture->get_url($PAGE)->out(false),
                'points' => number_format($points),
                'level' => $level,
                'progress' => $progress,
                'xptonext' => $ismaxlevel ? '' : number_format(max(0, $remaining)),
                'ismaxlevel' => $ismaxlevel,
                'iscurrentuser' => ($entry->userid == $this->currentuserid),
                'istop1' => ($entry->rank == 1),
                'istop2' => ($entry->rank == 2),
                'istop3' => ($entry->rank == 3),
                'profileurl' => (new \moodle_url('/user/profile.php', ['id' => $entry->userid]))->out(false),
            ];
        }

        // Current user summary.
        $usersummary = null;
        if ($this->userxp) {
            $userpoints = (int) $this->userxp->points;
            $userlevel = (int) $this->userxp->level;
            $userprogress = $levelcalc->get_level_progress($userpoints);
            $userismax = ($userlevel >= $levelcalc->get_max_level());
            $userremaining = $levelcalc->get_level_threshold($userlevel + 1) - $userpoints;

            $usersummary = [
                'rank' => $this->userrank,
                'totalcount' => $this->totalcount,
                'points' => number_format($userpoints),
                'level' => $userlevel,
                'progress' => $userprogress,
                'xptonext' => $userismax ? '' : number_format(max(0, $userremaining)),
                'ismaxlevel' => $userismax,
            ];
        }

        // Pagination.
        $totalpages = ceil($this->totalcount / $this->perpage);
        $haspages = $totalpages > 1;
        $pages = [];
        if ($haspages) {
            for ($i = 0; $i < $totalpages; $i++) {
                $pages[] = [
                    'pagenum' => $i + 1,
                    'page' => $i,
                    'isactive' => ($i == $this->page),
                    'url' => (new \moodle_url('/local/xp/leaderboard.php', [
                        'courseid' => $this->courseid,
                        'page' => $i,
                    ]))->out(false),
                ];
            }
        }

        return [
            'entries' => $entries,
            'hasentries' => !empty($entries),
            'totalcount' => $this->totalcount,
            'courseid' => $this->courseid,
            'iscourseview' => ($this->courseid > 0),
            'usersummary' => $usersummary,
            'hasusersummary' => ($usersummary !== null),
            'haspages' => $haspages,
            'pages' => $pages,
            'hasprevious' => ($this->page > 0),
            'hasnext' => ($this->page < $totalpages - 1),
            'previousurl' => ($this->page > 0) ? (new \moodle_url('/local/xp/leaderboard.php', [
                'courseid' => $this->courseid,
                'page' => $this->page - 1,
            ]))->out(false) : '',
            'nexturl' => ($this->page < $totalpages - 1) ? (new \moodle_url('/local/xp/leaderboard.php', [
                'courseid' => $this->courseid,
                'page' => $this->page + 1,
            ]))->out(false) : '',
        ];
    }
}
