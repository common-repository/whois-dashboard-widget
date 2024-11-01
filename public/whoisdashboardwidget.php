<?php

add_shortcode('whois', 'whois_show');
function whois_show( $atts ) {
	$sc.= '<div id="whois_form"><form method="post" id="whois" action=""><input type="hidden" name="action" value="whois_post" />';
	$sc.= wp_nonce_field( 'whoisnonce', 'post_nonce', true, false );
	$sc.= '<legend>'.__('Domain', 'whois').'</legend>';
  $sc.= '<input type="text" size="30" name="domain" id="domain" />';
	$sc.=' <input type="submit" id="whoissubmit" class="button" style="width:28%;" name="whoissubmit" value="'.__("Check", 'whois').'" /></form>';
	$sc.=' </div>';
	$sc.= '<div id="whois_work" style="display:none;"><img src="'.includes_url().'images/spinner.gif" alt="wait" /> '.__("Waiting for Whois servers", 'whois').'</div>';
	$sc.= '<div id="whois_result" class="whois_results" style="max-height:400px;overflow:scroll;overflow-x:hidden;"></div>';
	echo $sc;
}

// Whois Nonce
add_action('init','whoisnonce_create');
function whoisnonce_create() { $whoisnonce = wp_create_nonce('whoisnonce'); }

// Whois Dashboard Widget
function whois_dashboard_widgets() { wp_add_dashboard_widget( 'whois_show', __( 'Whois Search', 'bonestheme' ), 'whois_show' ); }
add_action( 'wp_dashboard_setup', 'whois_dashboard_widgets' );

// Enqueue Script
wp_enqueue_script( 'whois-ajax-request', plugin_dir_url( __FILE__ ) . 'assets/js/whois.js', array( 'jquery' ) );
wp_localize_script( 'whois-ajax-request', 'WhoisAjax', array(  'ajaxurl' => admin_url( 'admin-ajax.php' ), 'enter_domain' => __('Enter a Domain', 'whois') ) );

//Ajax Function Frontend
add_action('wp_ajax_whois_post', 'whois_post');
add_action('wp_ajax_nopriv_whois_post', 'whois_post' );

function whois_post(){
	global $whois_servers;
	
	//Check nonce
	if (! wp_verify_nonce($_POST['post_nonce'], 'whoisnonce') ) die('Security check');
		$whois=new psWhois;
		$result=$whois->lookup(trim($_POST['domain']), $whois_servers);
		//file_put_contents(WP_CONTENT_DIR."/logs/whois.log",$_POST['domain']."\n",FILE_APPEND );

		if (stristr($result,'Status: free')) {
			// $msg = $clickhere;
		} elseif (stristr($result,'available')) {
			// $msg = $clickhere;
		} elseif (stristr($result,'no match')) {
			// $msg = $clickhere;
		} elseif (stristr($result,'not found')) {
			// $msg = $clickhere;
		} elseif (stristr($result,'Status: invalid')) {
			// $msg = $clickhere;
		} else {
			// $msg = $clickhere;
		}
		$msg = '<p>'.$msg.'</p>';
		$msg.= '<p>'.nl2br($result).'</p>';
		$response = json_encode( array( 'success' => true , 'msg' => $msg ) );
		header( "Content-Type: application/json" );
		echo $response;

	die();
}

class psWhois{
	public function lookup($domain,$whoisservers) {
		$domain = trim($domain); // Remove space from start and end of domain
		if(substr(strtolower($domain), 0, 7) == "http://") $domain = substr($domain, 7); // remove http://
		if(substr(strtolower($domain), 0, 4) == "www.") $domain = substr($domain, 4); // remove www
		if(preg_match("/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/",$domain))
			return $this->queryWhois("whois.lacnic.net",$domain);
		elseif(preg_match("/^([-a-z0-9]{2,100})\.([a-z\.]{2,8})$/i",$domain))
		{
			$domain_parts = explode(".", $domain);
			$tld = strtolower(array_pop($domain_parts));
			$server = $whoisservers[$tld][0];
			if(!$server) {
				return "Error: No appropriate Whois server found for $domain domain!";
			}
			$res=$this->queryWhois($server,$domain);
			if ( preg_match("/Whois Server: (.*)/", $res, $matches) == 1 ) {
				//echo "Success ".$matches[1];
				$res=$this->queryWhois($matches[1],$domain);
			}
			return $res;
		}
		else
			return "Invalid Input";
	}
	private function queryWhois($server,$domain) {
		$fp = @fsockopen($server, 43, $errno, $errstr, 5) or WhoisTimeout();
		if($server=="whois.verisign-grs.com")
			$domain="=".$domain;
				fputs($fp, $domain . "\r\n");
				$out = "";
		while(!feof($fp)){
			$out .= fgets($fp);
		}
		fclose($fp);
		return $out;
	}
}

function WhoisTimeout() {
	$msg = __('Whois Server timeout', 'whois');
	$response = json_encode( array( 'success' => true , 'msg' => $msg ) );
	header( "Content-Type: application/json" );
	echo $response;
	die();
}

// Server Array
$whois_servers = array(
"com"               =>  array("whois.verisign-grs.com","whois.crsnic.net"),
"net"               =>  array("whois.verisign-grs.com","whois.crsnic.net"),
"org"               =>  array("whois.pir.org","whois.publicinterestregistry.net"),
"info"              =>  array("whois.afilias.info","whois.afilias.net"),
"biz"               =>  array("whois.neulevel.biz"),
"us"                =>  array("whois.nic.us"),
"uk"                =>  array("whois.nic.uk"),
"ca"                =>  array("whois.cira.ca"),
"tel"               =>  array("whois.nic.tel"),
"ie"                =>  array("whois.iedr.ie","whois.domainregistry.ie"),
"it"                =>  array("whois.nic.it"),
"li"                =>  array("whois.nic.li"),
"no"                =>  array("whois.norid.no"),
"cc"                =>  array("whois.nic.cc"),
"eu"                =>  array("whois.eu"),
"nu"                =>  array("whois.nic.nu"),
"au"                =>  array("whois.aunic.net","whois.ausregistry.net.au"),
"de"                =>  array("whois.denic.de"),
"ws"                =>  array("whois.worldsite.ws","whois.nic.ws","www.nic.ws"),
"sc"                =>  array("whois2.afilias-grs.net"),
"mobi"              =>  array("whois.dotmobiregistry.net"),
"pro"               =>  array("whois.registrypro.pro","whois.registry.pro"),
"edu"               =>  array("whois.educause.net","whois.crsnic.net"),
"tv"                =>  array("whois.nic.tv","tvwhois.verisign-grs.com"),
"travel"            =>  array("whois.nic.travel"),
"name"              =>  array("whois.nic.name"),
"in"                =>  array("whois.inregistry.net","whois.registry.in"),
"me"                =>  array("whois.nic.me","whois.meregistry.net"),
"at"                =>  array("whois.nic.at"),
"be"                =>  array("whois.dns.be"),
"cn"                =>  array("whois.cnnic.cn","whois.cnnic.net.cn"),
"asia"              =>  array("whois.nic.asia"),
"ru"                =>  array("whois.ripn.ru","whois.ripn.net"),
"ro"                =>  array("whois.rotld.ro"),
"aero"              =>  array("whois.aero"),
"fr"                =>  array("whois.nic.fr"),
"se"                =>  array("whois.iis.se","whois.nic-se.se","whois.nic.se"),
"nl"                =>  array("whois.sidn.nl","whois.domain-registry.nl"),
"nz"                =>  array("whois.srs.net.nz","whois.domainz.net.nz"),
"mx"                =>  array("whois.nic.mx"),
"tw"                =>  array("whois.apnic.net","whois.twnic.net.tw"),
"ch"                =>  array("whois.nic.ch"),
"hk"                =>  array("whois.hknic.net.hk"),
"ac"                =>  array("whois.nic.ac"),
"ae"                =>  array("whois.nic.ae"),
"af"                =>  array("whois.nic.af"),
"ag"                =>  array("whois.nic.ag"),
"al"                =>  array("whois.ripe.net"),
"am"                =>  array("whois.amnic.net"),
"as"                =>  array("whois.nic.as"),
"az"                =>  array("whois.ripe.net"),
"ba"                =>  array("whois.ripe.net"),
"bg"                =>  array("whois.register.bg"),
"bi"                =>  array("whois.nic.bi"),
"bj"                =>  array("www.nic.bj"),
"br"                =>  array("whois.nic.br"),
"bt"                =>  array("whois.netnames.net"),
"by"                =>  array("whois.ripe.net"),
"bz"                =>  array("whois.belizenic.bz"),
"cd"                =>  array("whois.nic.cd"),
"ck"                =>  array("whois.nic.ck"),
"cl"                =>  array("nic.cl"),
"coop"              =>  array("whois.nic.coop"),
"cx"                =>  array("whois.nic.cx"),
"cy"                =>  array("whois.ripe.net"),
"cz"                =>  array("whois.nic.cz"),
"dk"                =>  array("whois.dk-hostmaster.dk"),
"dm"                =>  array("whois.nic.cx"),
"dz"                =>  array("whois.ripe.net"),
"ee"                =>  array("whois.eenet.ee"),
"eg"                =>  array("whois.ripe.net"),
"es"                =>  array("whois.ripe.net"),
"fi"                =>  array("whois.ficora.fi"),
"fo"                =>  array("whois.ripe.net"),
"gb"                =>  array("whois.ripe.net"),
"ge"                =>  array("whois.ripe.net"),
"gl"                =>  array("whois.ripe.net"),
"gm"                =>  array("whois.ripe.net"),
"gov"               =>  array("whois.nic.gov"),
"gr"                =>  array("whois.ripe.net"),
"gs"                =>  array("whois.adamsnames.tc"),
"hm"                =>  array("whois.registry.hm"),
"hn"                =>  array("whois2.afilias-grs.net"),
"hr"                =>  array("whois.ripe.net"),
"hu"                =>  array("whois.ripe.net"),
"il"                =>  array("whois.isoc.org.il"),
"int"               =>  array("whois.isi.edu"),
"iq"                =>  array("vrx.net"),
"ir"                =>  array("whois.nic.ir"),
"is"                =>  array("whois.isnic.is"),
"je"                =>  array("whois.je"),
"jp"                =>  array("whois.jprs.jp"),
"kg"                =>  array("whois.domain.kg"),
"kr"                =>  array("whois.nic.or.kr"),
"la"                =>  array("whois2.afilias-grs.net"),
"lt"                =>  array("whois.domreg.lt"),
"lu"                =>  array("whois.restena.lu"),
"lv"                =>  array("whois.nic.lv"),
"ly"                =>  array("whois.lydomains.com"),
"ma"                =>  array("whois.iam.net.ma"),
"mc"                =>  array("whois.ripe.net"),
"md"                =>  array("whois.nic.md"),
"mil"               =>  array("whois.nic.mil"),
"mk"                =>  array("whois.ripe.net"),
"ms"                =>  array("whois.nic.ms"),
"mt"                =>  array("whois.ripe.net"),
"mu"                =>  array("whois.nic.mu"),
"my"                =>  array("whois.mynic.net.my"),
"nf"                =>  array("whois.nic.cx"),
"pl"                =>  array("whois.dns.pl"),
"pr"                =>  array("whois.nic.pr"),
"pt"                =>  array("whois.dns.pt"),
"sa"                =>  array("saudinic.net.sa"),
"sb"                =>  array("whois.nic.net.sb"),
"sg"                =>  array("whois.nic.net.sg"),
"sh"                =>  array("whois.nic.sh"),
"si"                =>  array("whois.arnes.si"),
"sk"                =>  array("whois.sk-nic.sk"),
"sm"                =>  array("whois.ripe.net"),
"st"                =>  array("whois.nic.st"),
"su"                =>  array("whois.ripn.net"),
"tc"                =>  array("whois.adamsnames.tc"),
"tf"                =>  array("whois.nic.tf"),
"th"                =>  array("whois.thnic.net"),
"tj"                =>  array("whois.nic.tj"),
"tk"                =>  array("whois.nic.tk"),
"tl"                =>  array("whois.domains.tl"),
"tm"                =>  array("whois.nic.tm"),
"tn"                =>  array("whois.ripe.net"),
"to"                =>  array("whois.tonic.to"),
"tp"                =>  array("whois.domains.tl"),
"tr"                =>  array("whois.nic.tr"),
"ua"                =>  array("whois.ripe.net"),
"uy"                =>  array("nic.uy"),
"uz"                =>  array("whois.cctld.uz"),
"va"                =>  array("whois.ripe.net"),
"vc"                =>  array("whois2.afilias-grs.net"),
"ve"                =>  array("whois.nic.ve"),
"vg"                =>  array("whois.adamsnames.tc"),
"yu"                =>  array("whois.ripe.net")
);