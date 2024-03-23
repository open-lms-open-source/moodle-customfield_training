<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace customfield_training\hook;

use core\hook\described_hook;

/**
 * Allows plugins to update their completion based on training.
 *
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_updated implements described_hook {

    protected $userid;
    protected $frameworkids;

    /**
     * Creates new hook.
     *
     * @param int $userid
     * @param int[] $frameworkids
     */
    public function __construct(int $userid, array $frameworkids) {
        $this->userid = $userid;
        $this->frameworkids = $frameworkids;
    }

    /**
     * Updated completion for given framework ids.
     *
     * @return int[]
     */
    public function get_frameworkids(): array {
        return $this->frameworkids;
    }

    /**
     * Updated completion for given user.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Describes the hook purpose.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Allows plugins to trigger completion recalculation depending on training';
    }

    /**
     * List of tags that describe this hook.
     *
     * @return string[]
     */
    public static function get_hook_tags(): array {
        return ['trainingss'];
    }
}
