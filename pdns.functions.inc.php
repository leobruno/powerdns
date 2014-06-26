<?php
	/**
	 * Power DNS Related Functionality
	 * Last Changed: $LastChangedDate$
	 * @author $Author$
	 * @version $Revision$
	 * @copyright 2012
	 * @package MyAdmin
	 * @category DNS 
	 */

	if (!function_exists('_'))
	{
		function _($text)
		{
			return $text;
		}
	}

	include (INCLUDE_ROOT . '/db/class.db_mdb2.functions.inc.php');
	//	include(INCLUDE_ROOT . '/dns/poweradmin/inc/database.inc.php');
	include (INCLUDE_ROOT . '/dns/poweradmin/inc/dns.inc.php');
	include (INCLUDE_ROOT . '/dns/poweradmin/inc/record.inc.php');
	include (INCLUDE_ROOT . '/dns/poweradmin/inc/error.inc.php');

	global $db_mdb2;
	$db_mdb2 = new db_mdb2('poweradmin', 'poweradmin', POWERADMIN_PASSWORD, POWERADMIN_HOST);

	$valid_tlds = array(
		'ac', 'academy', 'actor', 'ad', 'ae', 'aero', 'af', 'ag', 'agency', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'arpa', 'as', 
		'asia', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bar', 'bargains', 'bb', 'bd', 'be', 'berlin', 'best', 'bf', 'bg', 'bh', 'bi', 
		'bid', 'bike', 'biz', 'bj', 'blue', 'bm', 'bn', 'bo', 'boutique', 'br', 'bs', 'bt', 'build', 'builders', 'buzz', 'bv', 'bw', 
		'by', 'bz', 'ca', 'cab', 'camera', 'camp', 'cards', 'careers', 'cat', 'catering', 'cc', 'cd', 'center', 'ceo', 'cf', 'cg', 'ch', 
		'cheap', 'christmas', 'ci', 'ck', 'cl', 'cleaning', 'clothing', 'club', 'cm', 'cn', 'co', 'codes', 'coffee', 'com', 'community', 
		'company', 'computer', 'condos', 'construction', 'contractors', 'cool', 'coop', 'cr', 'cruises', 'cu', 'cv', 'cw', 'cx', 'cy', 
		'cz', 'dance', 'dating', 'de', 'democrat', 'diamonds', 'directory', 'dj', 'dk', 'dm', 'dnp', 'do', 'domains', 'dz', 'ec', 'edu', 
		'education', 'ee', 'eg', 'email', 'enterprises', 'equipment', 'er', 'es', 'estate', 'et', 'eu', 'events', 'expert', 'exposed', 
		'farm', 'fi', 'fish', 'fj', 'fk', 'flights', 'florist', 'fm', 'fo', 'foundation', 'fr', 'futbol', 'ga', 'gallery', 'gb', 'gd', 
		'ge', 'gf', 'gg', 'gh', 'gi', 'gift', 'gl', 'glass', 'gm', 'gn', 'gov', 'gp', 'gq', 'gr', 'graphics', 'gs', 'gt', 'gu', 'guitars', 
		'guru', 'gw', 'gy', 'hk', 'hm', 'hn', 'holdings', 'holiday', 'house', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'immobilien', 
		'in', 'industries', 'info', 'ink', 'institute', 'int', 'international', 'io', 'iq', 'ir', 'is', 'it', 'je', 'jetzt', 'jm', 'jo', 
		'jobs', 'jp', 'kaufen', 'ke', 'kg', 'kh', 'ki', 'kim', 'kitchen', 'kiwi', 'km', 'kn', 'koeln', 'kp', 'kr', 'kred', 'kw', 'ky', 
		'kz', 'la', 'land', 'lb', 'lc', 'li', 'lighting', 'limo', 'link', 'lk', 'lr', 'ls', 'lt', 'lu', 'luxury', 'lv', 'ly', 'ma', 
		'maison', 'management', 'mango', 'marketing', 'mc', 'md', 'me', 'menu', 'mg', 'mh', 'mil', 'mk', 'ml', 'mm', 'mn', 'mo', 'mobi', 
		'moda', 'monash', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'museum', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nagoya', 'name', 'nc', 'ne', 
		'net', 'neustar', 'nf', 'ng', 'ni', 'ninja', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'okinawa', 'om', 'onl', 'org', 'pa', 'partners', 
		'parts', 'pe', 'pf', 'pg', 'ph', 'photo', 'photography', 'photos', 'pics', 'pink', 'pk', 'pl', 'plumbing', 'pm', 'pn', 'post', 
		'pr', 'pro', 'productions', 'properties', 'ps', 'pt', 'pub', 'pw', 'py', 'qa', 'qpon', 're', 'recipes', 'red', 'rentals', 
		'repair', 'report', 'reviews', 'rich', 'ro', 'rs', 'ru', 'ruhr', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sexy', 'sg', 'sh', 
		'shiksha', 'shoes', 'si', 'singles', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'social', 'solar', 'solutions', 'sr', 'st', 'su', 
		'supplies', 'supply', 'support', 'sv', 'sx', 'sy', 'systems', 'sz', 'tattoo', 'tc', 'td', 'technology', 'tel', 'tf', 'tg', 'th', 
		'tienda', 'tips', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'today', 'tokyo', 'tools', 'tp', 'tr', 'training', 'travel', 'tt', 'tv', 
		'tw', 'tz', 'ua', 'ug', 'uk', 'uno', 'us', 'uy', 'uz', 'va', 'vacations', 'vc', 've', 'ventures', 'vg', 'vi', 'viajes', 'villas', 
		'vision', 'vn', 'vote', 'voting', 'voto', 'voyage', 'vu', 'wang', 'watch', 'wed', 'wf', 'wien', 'wiki', 'works', 'ws', 'xn--3bst00m', 
		'xn--3ds443g', 'xn--3e0b707e', 'xn--45brj9c', 'xn--55qw42g', 'xn--55qx5d', 'xn--6frz82g', 'xn--6qq986b3xl', 'xn--80ao21a', 
		'xn--80asehdb', 'xn--80aswg', 'xn--90a3ac', 'xn--c1avg', 'xn--cg4bki', 'xn--clchc0ea0b2g2a9gcd', 'xn--d1acj3b', 'xn--fiq228c5hs', 
		'xn--fiq64b', 'xn--fiqs8s', 'xn--fiqz9s', 'xn--fpcrj9c3d', 'xn--fzc2c9e2c', 'xn--gecrj9c', 'xn--h2brj9c', 'xn--i1b6b1a6a2e', 
		'xn--io0a7i', 'xn--j1amh', 'xn--j6w193g', 'xn--kprw13d', 'xn--kpry57d', 'xn--l1acc', 'xn--lgbbat1ad8j', 'xn--mgb9awbf', 'xn--mgba3a4f16a', 
		'xn--mgbaam7a8h', 'xn--mgbab2bd', 'xn--mgbayh7gpa', 'xn--mgbbh1a71e', 'xn--mgbc0a9azcg', 'xn--mgberp4a5d4ar', 'xn--mgbx4cd0ab', 
		'xn--ngbc5azd', 'xn--nqv7f', 'xn--nqv7fs00ema', 'xn--o3cw4h', 'xn--ogbpf8fl', 'xn--p1ai', 'xn--pgbs0dh', 'xn--q9jyb4c', 'xn--rhqv96g', 
		'xn--s9brj9c', 'xn--unup4y', 'xn--wgbh1c', 'xn--wgbl6a', 'xn--xkc2al3hye2a', 'xn--xkc2dl3a5ee0h', 'xn--yfro4i67o', 'xn--ygbi2ammx', 
		'xn--zfr164b', 'xxx', 'xyz', 'ye', 'yt', 'za', 'zm', 'zone', 'zw'
	);

	// Array of the available zone types
	$server_types = array(
		"MASTER",
		"SLAVE",
		"NATIVE");

	// $rtypes - array of possible record types
	$rtypes = array(
		'A',
		'AAAA',
		'CNAME',
		'HINFO',
		'MX',
		'NAPTR',
		'NS',
		'PTR',
		'SOA',
		'SPF',
		'SRV',
		'SSHFP',
		'TXT',
		'RP');

	// If fancy records is enabled, extend this field.
	if (isset($dns_fancy) && $dns_fancy)
	{
		$rtypes[14] = 'URL';
		$rtypes[15] = 'MBOXFW';
		$rtypes[16] = 'CURL';
	}

	/**
	 * is_valid_email()
	 * 
	 * @param mixed $address
	 * @return
	 */
	function is_valid_email($address)
	{
		$fields = preg_split("/@/", $address, 2);
		if ((!preg_match("/^[0-9a-z]([-_.]?[0-9a-z])*$/i", $fields[0])) || (!isset($fields[1]) || $fields[1] == '' || !is_valid_hostname_fqdn($fields[1], 0)))
		{
			return false;
		}
		return true;
	}

	// Set timezone (required for PHP5)
	/**
	 * set_timezone()
	 * 
	 * @return
	 */
	function set_timezone()
	{
		global $timezone;

		if (function_exists('date_default_timezone_set'))
		{
			if (isset($timezone))
			{
				date_default_timezone_set($timezone);
			}
			else
				if (!ini_get('date.timezone'))
				{
					date_default_timezone_set('UTC');
				}
		}
	}
	if (!function_exists('error'))
	{
		/**
		 * error()
		 * 
		 * @param mixed $msg
		 * @return
		 */
		function error($msg)
		{
			if ($msg)
			{
				add_output("     <div class=\"error\">Error: " . $msg . "</div>\n");
			}
			else
			{
				add - output("     <div class=\"error\">" . 'An unknown error has occurred.' . "</div>\n");
			}
		}
	}
	/**
	 * isError()
	 * 
	 * @param mixed $result
	 * @return
	 */
	function isError($result)
	{
		return $result->error;
	}
?>
