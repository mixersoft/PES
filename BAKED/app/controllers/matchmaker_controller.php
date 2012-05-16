<?php 
class MatchmakerController extends AppController {

    var $name = 'Matchmaker';
    var $uses = array('Snappi');
    var $components = array('RequestHandler', 'Session');
    var $helpers = array('Html', 'Form', 'Time', 'Text');
    
    function beforeFilter() {
        parent::beforeFilter();
        /*
         *	These actions are allowed for all users
         */
        if (stripos('snaphappi.com', env('SERVER_NAME'))) {
            // castingCall, proxy is guest permissions only at snaphappi.com
            $this->Auth->allow(array('proxy'));
        } else
            $this->Auth->allow(array('castingCall', 'proxy', 'catalog'));
    }
    
    function beforeRender() {
        if ($this->RequestHandler->isXml())
            $this->header('Content-Type: text/xml');
    }
    
    function home() {
        $this->layout = "yui";
    }
    
    function test() {
    
    }
    
    function parseXml($path) {
        // import XML class
        App::import('Xml');
        // now parse it
        $parsed_xml = & new XML($path);
        $parsed_xml = Set::reverse($parsed_xml); // this is what i call magic
        
        // see the returned array
        //        debug($parsed_xml);
        return $parsed_xml;
    }
    

    /*
     * Temp Controller
     * Import Groupings from mean-shift output into Tag DB
     *
     */
    function rebuildMeanShiftGroupings($woid) {
        $this->autoRender = null;
        $this->layout = 'xml';
        $errors = array();
        $converted = array();
        /*
         * run meanshift to create groupings file
         */
        App::import('Component', 'Meanshift');
        $Meanshift = & new MeanshiftComponent();
        $Meanshift->startup($this);
        
        /*
         * call HTTP GET to get auditions asXml (no groups)
         */
        App::import('Component', 'HttpClient');
        $this->httpClient = & new HttpClientComponent();
        $this->httpClient->startup($this);
        $url = 'http://'.env('HTTP_HOST')."/matchmaker/castingCall.xml";
        //		"wo=($woid}&nogroups=1&perpage=all"
        $params = array('wo'=>$woid, 'nogroups'=>1, 'perpage'=>2200);
        $auditionXML = $this->httpClient->submit($url, $params, $method = "GET");
        
        /*
         * send XML as string to Meanshift controller. 
         * - calculate meanshift groupings, saved to tmp file
         * - read tmp file for XML
         */
        $ret = $Meanshift->getGroupings($auditionXML);
        if (! empty($ret['errors'])) {
            $errors = $ret['errors'];
            debug($errors);
        }
        $pathToGroupings = @if_e($ret['out'], null);
        
        /*
         * READ meanshift output file, and parse
         */
        $xmlAsArray = $this->parseXml($pathToGroupings);
        //        debug($pathToGroupings);
        //        debug($xmlAsArray); exit;
        
        /*
         *  [0] => Array (
         [id] => snappi-substitute-0
         [Type] => Sequence
         [score] => 0.0
         [AuditionREF] => Array
         (
         [0] => Array
         (
         [idref] => snappi-audition-8947
         [score] => 1.0
         )
         [1] => Array
         (
         [idref] => snappi-audition-8948
         [score] => 1.0
         )
         )
         )
         */
        $arrSubstitutions = $xmlAsArray['CastingCall']['Substitutions']['Substitution'];
        /*
         [0] => Array(
         [id] => snappi-cluster-0
         [Type] => Event
         [score] => 1377.9
         [AuditionREF] => Array
         (
         [0] => Array
         (
         [idref] => snappi-audition-8946
         [score] => 0.65
         )
         [1] => Array
         (
         [idref] => snappi-audition-8947
         [score] => 0.71
         )
         )
         )
         */
        $arrClusters = $xmlAsArray['CastingCall']['Clusters']['Cluster'];
        // load Model
        App::import('model', 'Tag');
        $Tag = new Tag();
        
        // ****************************************************************
        // RESET (delete) meanshift Substitute and Cluster groups
        // events and substitutions do not have unique/meaningful names,
        // so always delete and re-create groups
        // delete existing Tags on replace
        
        //TODO: need to add this method to BAKED
        $Tag->resetMeanShift($woid);

        
        /*
         * TODO: BAKED. temporary. replace with user id when available
         */
        if (!$this->Session->check('PageGallery.SessionKey')) {
            $this->Session->write('PageGallery.SessionKey', sha1(time().'-'.rand()));
        }
        $pgSession = $this->Session->read('PageGallery.SessionKey');
        $userid = substr($pgSession, 0, 3);
        /*
         * end temp hack
         */
        
        
        /********************************************************************
         * Create Tag/Group for each meanshift substitute/cluster
         * 
         * 
         */
        $groups = array('Substitution'=>$arrSubstitutions, 'Cluster'=>$arrClusters);
        $types = array('Substitution', 'Cluster');
        
        foreach ($types as $type) {
            for ($i = 0; $i < count($groups[$type]); $i++) {
                $group = $groups[$type][$i];
                if (!isset($group['AuditionREF']) || !@is_array($group['AuditionREF'][0])) {
                    //                	debug($group);
                    continue; // 0 or 1 element in cluster, so skip
                }
                $Tag->create();
                $groupData = array_intersect_key($group, array('Type'=>null, 'score'=>null));
                $groupData['name'] = strtolower("snappi-{$type}-{$i}:").$userid; // must be unique
                $groupData['description'] = $group['Type'];
                $groupData['reserved'] = 3;
                //debug($groupData);
                //	debug($group);
                if ($Tag->save($groupData)) {
                    $tagId = $Tag->id;
                    $clip = strlen('snappi-audition-');
                    foreach ($group['AuditionREF'] as $audition) {
                        // create assets_tags record
                        $assetId = substr($audition['idref'], $clip);
                        //if (empty($assetId)) {
                        //	debug($group);
                        //	debug($audition); exit;
                        //}
                        $Tag->addAssetTag($assetId, $tagId);
                        //						$Tag->addAsset($assetId, $tagId);
                    }
                } else {
                    $errors[] = $groupData;
                }
            }
        }
    }



    function production($id = null) {
        $this->layout = 'xml';
        
        $presentationType = array();
        $presentationType['RenderSize'] = array('H'=>8);
        $presentationType['RenderUnitsPerInch'] = 1;
        $presentationType['CSS'] = "";
        $presentationType['Padding'] = "4px";
        $presentationType['BorderColor'] = "gray";
        $presentationType['MinDPI'] = 300;
        $presentationType['PreviewDPI'] = 72;
        $presentationType['SceneCount'] = array('Min'=>1, 'Suggested'=>7, 'Max'=>10);
        
        $presentation['id'] = '1';
        $presentation['RepeatAuditions'] = false;
        $presentation['RepeatArrangements'] = true;
        
        $local['schemaLocation'] = 'snaphappi.com W:\www\matchmaker-svn\matchmaker.xsd';
        $local['presentationType'] = $presentationType;
        $local['RepeatArrangements'] = true;
        
        //		debug ($presentationType);
        $this->set('presentationType', $presentationType);
        $this->set('presentation', $presentation);
    }

    
}
?>
