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

/**
 * Privacy provider tests.
 *
 * @package    mod_ompdf
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ompdf\privacy;

use core_privacy\local\metadata\collection;
use mod_ompdf\privacy\provider;

/**
 * Tests for the OMPDF Privacy API provider.
 *
 * @covers \mod_ompdf\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * The provider must describe every table and preference containing user data.
     */
    public function test_get_metadata(): void {
        $collection = new collection('mod_ompdf');
        $collection = provider::get_metadata($collection);
        $items = $collection->get_collection();

        $this->assertCount(3, $items);
        $names = array_map(static function ($item): string {
            return $item->get_name();
        }, $items);
        $this->assertContains('ompdf_analytics', $names);
        $this->assertContains('ompdf_annotations', $names);
        $this->assertContains('mod_ompdf_lastpage_', $names);
    }
}
