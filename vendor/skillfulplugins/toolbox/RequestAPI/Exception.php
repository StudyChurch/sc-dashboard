<?php
/**
 * SkillfulPlugins Plugin Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace SkillfulPlugins\RequestAPI;

use SkillfulPlugins\Exception as SK_Exception;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_API_Exception' ) ) :

	/**
	 * Plugin Framework API Exception - generic API Exception
	 */
	class Exception extends SK_Exception { }

endif;  // class exists check
