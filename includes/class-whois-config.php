<?php
/**
 * WHOIS Server Configuration
 * 
 * @package Domain_WHOIS_Checker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WHOIS Configuration Class
 */
class DWC_WHOIS_Config {

    /**
     * Get WHOIS server configuration
     * 
     * @return array WHOIS server configuration array
     */
    public static function get_whois_servers() {
        return array(
            // Pakistani Domains - Comprehensive Support
            array(
                "extensions" => ".pk,.com.pk,.net.pk,.org.pk,.edu.pk,.web.pk,.biz.pk,.fam.pk,.gok.pk,.gob.pk,.gov.pk",
                "uri" => "http://pk6.pknic.net.pk/pk5/lookup.PK?name=",
                "available" => "Domain not found"
            ),
            // Additional Pakistani Domain Extensions
            array(
                "extensions" => ".info.pk,.tv.pk,.online.pk,.store.pk,.tech.pk,.pro.pk",
                "uri" => "http://pk6.pknic.net.pk/pk5/lookup.PK?name=",
                "available" => "Domain not found"
            ),
            // Backup WHOIS server for .pk domains
            array(
                "extensions" => ".pk,.com.pk,.net.pk,.org.pk,.edu.pk,.web.pk,.biz.pk,.fam.pk",
                "uri" => "socket://whois.pknic.net.pk",
                "available" => "Domain not found"
            ),
            array(
                "extensions" => ".com,.net,.es,.com.es,.nom.es,.gob.es,.edu.es",
                "uri" => "socket://whois.crsnic.net",
                "available" => "No match for"
            ),
            array(
                "extensions" => ".org,.ngo,.ong",
                "uri" => "socket://whois.publicinterestregistry.net",
                "available" => "Domain not found"
            ),
            array(
                "extensions" => ".uk,.co.uk,.net.uk,.org.uk,.ltd.uk,.plc.uk,.me.uk",
                "uri" => "socket://whois.nic.uk",
                "available" => "No match"
            ),
            array(
                "extensions" => ".edu,.mil",
                "uri" => "socket://whois.internic.net",
                "available" => "No match for"
            ),
            array(
                "extensions" => ".br.com,.cn.com,.eu.com,.no.com,.qc.com,.sa.com,.se.com,.se.net,.us.com,.uy.com,.za.com,.uk.com,.uk.net,.gb.com,.gb.net,.online,.site",
                "uri" => "socket://whois.centralnic.com",
                "available" => "DOMAIN NOT FOUND"
            ),
            array(
                "extensions" => ".ink",
                "uri" => "socket://whois.nic.ink",
                "available" => "No Data Found"
            ),
            array(
                "extensions" => ".com.de",
                "uri" => "socket://whois.centralnic.com",
                "available" => "Status: free"
            ),
            array(
                "extensions" => ".ac,.co.ac",
                "uri" => "socket://whois.nic.ac",
                "available" => "Domain not found"
            ),
            array(
                "extensions" => ".af",
                "uri" => "socket://whois.nic.af",
                "available" => "No Object Found"
            ),
            array(
                "extensions" => ".am",
                "uri" => "socket://whois.amnic.net",
                "available" => "No match"
            ),
            array(
                "extensions" => ".as",
                "uri" => "socket://whois.nic.as",
                "available" => "NOT FOUND"
            ),
            array(
                "extensions" => ".at,.ac.at,.co.at,.gv.at,.or.at",
                "uri" => "socket://whois.nic.at",
                "available" => "nothing found"
            ),
            array(
                "extensions" => ".au,.asn.au,.com.au,.edu.au,.org.au,.net.au,.id.au",
                "uri" => "socket://domaincheck.auda.org.au",
                "available" => "---Available"
            ),
            array(
                "extensions" => ".be,.ac.be",
                "uri" => "socket://whois.dns.be",
                "available" => "Status:\tAVAILABLE"
            ),
            array(
                "extensions" => ".br,.adm.br,.adv.br,.am.br,.arq.br,.art.br,.bio.br,.cng.br,.cnt.br,.com.br,.ecn.br,.eng.br,.esp.br,.etc.br,.eti.br,.fm.br,.fot.br,.fst.br,.g12.br,.gov.br,.ind.br,.inf.br,.jor.br,.lel.br,.med.br,.mil.br,.net.br,.nom.br,.ntr.br,.odo.br,.org.br,.ppg.br,.pro.br,.psc.br,.psi.br,.rec.br,.slg.br,.tmp.br,.tur.br,.tv.br,.vet.br,.zlg.br",
                "uri" => "socket://whois.nic.br",
                "available" => "No match for"
            ),
            array(
                "extensions" => ".ca",
                "uri" => "socket://whois.cira.ca",
                "available" => "Not found"
            ),
            array(
                "extensions" => ".cc",
                "uri" => "socket://whois.nic.cc",
                "available" => "No match"
            ),
            array(
                "extensions" => ".cn,.ac.cn,.com.cn,.edu.cn,.gov.cn,.net.cn,.org.cn,.bj.cn,.sh.cn,.tj.cn,.cq.cn,.he.cn,.nm.cn,.ln.cn,.jl.cn,.hl.cn,.js.cn,.zj.cn,.ah.cn,.hb.cn,.hn.cn,.gd.cn,.gx.cn,.hi.cn,.sc.cn,.gz.cn,.yn.cn,.xz.cn,.sn.cn,.gs.cn,.qh.cn,.nx.cn,.xj.cn,.tw.cn,.hk.cn,.mo.cn",
                "uri" => "socket://whois.cnnic.net.cn",
                "available" => "No matching record"
            ),
            array(
                "extensions" => ".de",
                "uri" => "socket://whois.denic.de",
                "available" => "Status: free"
            ),
            array(
                "extensions" => ".fr,.tm.fr,.com.fr,.asso.fr,.presse.fr",
                "uri" => "socket://whois.nic.fr",
                "available" => "NOT FOUND"
            ),
            array(
                "extensions" => ".info,.blue,.kim,.pink,.black,.green,.lgbt,.poker,.red,.vote,.voto,.archi,.bio,.ski,.bet,.promo,.pet,.lotto",
                "uri" => "socket://whois.afilias.net",
                "available" => "Domain not found"
            ),
            array(
                "extensions" => ".biz",
                "uri" => "socket://whois.biz",
                "available" => "No Data Found"
            ),
            array(
                "extensions" => ".name",
                "uri" => "socket://whois.nic.name",
                "available" => "No match"
            ),
            array(
                "extensions" => ".ie",
                "uri" => "socket://whois.domainregistry.ie",
                "available" => "Not found"
            ),
            array(
                "extensions" => ".us",
                "uri" => "socket://whois.nic.us",
                "available" => "No Data Found"
            ),
            array(
                "extensions" => ".mobi",
                "uri" => "socket://whois.nic.mobi",
                "available" => "Domain not found"
            ),
            array(
                "extensions" => ".eu",
                "uri" => "socket://whois.eu",
                "available" => "Status: AVAILABLE"
            ),
            array(
                "extensions" => ".io",
                "uri" => "socket://whois.nic.io",
                "available" => "---Domain not found"
            ),
            array(
                "extensions" => ".tv",
                "uri" => "socket://whois.nic.tv",
                "available" => "No Data Found"
            ),
            array(
                "extensions" => ".me",
                "uri" => "socket://whois.nic.me",
                "available" => "NOT FOUND"
            ),
            array(
                "extensions" => ".asia",
                "uri" => "socket://whois.nic.asia",
                "available" => "NOT FOUND"
            ),
            array(
                "extensions" => ".top",
                "uri" => "socket://whois.nic.top",
                "available" => "The queried object does not exist"
            ),
            array(
                "extensions" => ".xyz",
                "uri" => "socket://whois.nic.xyz",
                "available" => "DOMAIN NOT FOUND"
            ),
            array(
                "extensions" => ".cloud",
                "uri" => "socket://whois.nic.cloud",
                "available" => "No Data Found"
            ),
            array(
                "extensions" => ".store",
                "uri" => "socket://whois.nic.store",
                "available" => "DOMAIN NOT FOUND"
            ),
            array(
                "extensions" => ".tech",
                "uri" => "socket://whois.nic.tech",
                "available" => "DOMAIN NOT FOUND"
            ),
            array(
                "extensions" => ".blog",
                "uri" => "socket://whois.nic.blog",
                "available" => "DOMAIN NOT FOUND"
            ),
            array(
                "extensions" => ".shop",
                "uri" => "socket://whois.nic.shop",
                "available" => "DOMAIN NOT FOUND"
            ),
            array(
                "extensions" => ".dev",
                "uri" => "socket://whois.nic.google",
                "available" => "Domain not found"
            ),
            array(
                "extensions" => ".app",
                "uri" => "socket://whois.nic.google",
                "available" => "Domain not found"
            )
        );
    }

    /**
     * Find WHOIS server for a domain extension
     * 
     * @param string $extension Domain extension (e.g., .com, .org)
     * @return array|false WHOIS server config or false if not found
     */
    public static function get_server_for_extension($extension) {
        $extension = strtolower($extension);
        $servers = self::get_whois_servers();

        foreach ($servers as $server) {
            $extensions = explode(',', $server['extensions']);
            $extensions = array_map('trim', $extensions);
            
            if (in_array($extension, $extensions)) {
                return $server;
            }
        }

        return false;
    }

    /**
     * Get domain extension from domain name
     * 
     * @param string $domain Domain name
     * @return string Domain extension
     */
    public static function get_domain_extension($domain) {
        $domain = strtolower(trim($domain));
        
        // Remove protocol if present
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove www if present
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Split domain by dots
        $parts = explode('.', $domain);
        
        if (count($parts) < 2) {
            return false;
        }

        // Try to match multi-level extensions first (e.g., .co.uk, .com.au)
        if (count($parts) >= 3) {
            $two_level = '.' . $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            if (self::get_server_for_extension($two_level)) {
                return $two_level;
            }
        }

        // Try single level extension
        $single_level = '.' . $parts[count($parts) - 1];
        return $single_level;
    }
}