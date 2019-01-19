<?php
/**
 * Define the Request class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace StudyChurch\BibleAPI;

use SkillfulPlugins\RequestAPI\RequestJSON;
use SkillfulPlugins\RequestAPI\Exception;
use SkillfulPlugins\Helpers;

defined( 'ABSPATH' ) or exit;

/**
 * The MoneyPit BibleAPI API request class.
 *
 * @since 1.0.0
 */
class Request extends RequestJSON {

	/**
	 * Construct the AvaTax request object.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->method = 'GET';
	}

	public function get_passage( $search_param ) {
		$this->path = add_query_arg( 'q', $search_param, $this->path );
	}

}
