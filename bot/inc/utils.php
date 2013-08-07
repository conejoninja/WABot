<?php

function processMessages($raw, $db = true) {
    $log = Log::newInstance();
    $msgs = array();
    $l = count($raw);
    for($k=0;$k<$l;$k++) {
        $msg = array();
        if(isset($raw[$k]->_attributeHash) && isset($raw[$k]->_attributeHash['t']) && isset($raw[$k]->_attributeHash['id'])) {
            if($raw[$k]->_attributeHash['t']>(time()-3600) && !$log->isProcessed($raw[$k]->_attributeHash['id'])) {
                $msg['from'] = @$raw[$k]->_attributeHash['from'];
                $msg['author'] = isset($raw[$k]->_attributeHash['author'])?$raw[$k]->_attributeHash['author']:@$raw[$k]->_attributeHash['from'];
                $msg['id'] = $raw[$k]->_attributeHash['id'];
                $msg['t'] = $raw[$k]->_attributeHash['t'];
                $msg['nickname'] = '';
                $msg['body'] = '';
                if(isset($raw[$k]->_children)) {
                    foreach($raw[$k]->_children as $m) {
                        if(isset($m->_tag) && $m->_tag=='notify') {
                            $msg['nickname'] = $m->_attributeHash['name'];
                        }
                        if(isset($m->_tag) && $m->_tag=='body') {
                            $msg['body'] = $m->_data;
                        }
                    }
                    if($msg['body']!='') {
                        $log->insert(
                            array(
                                'fk_i_chat_id' => $msg['from']
                                ,'s_author' => $msg['author']
                                ,'s_id' => $msg['id']
                                ,'i_timestamp' => $msg['t']
                                ,'s_nickname' => $msg['nickname']
                                ,'s_message' => $msg['body']
                            )
                        );
                        $msgs[] = $msg;
                    }
                }
            }
        }
    }
    return $msgs;
}


function listMemes($dir) {
    $list = '';
    if($handler=opendir($dir)) {
        while(false!==($file=readdir($handler))) {
            if($file!='.' && $file!='..' && $file!='.DS_Store') {
                $list .= str_replace(".png", "", str_replace(".jpg", "", $file)).PHP_EOL;
            }
        }
        closedir($handler);
    }
    return $list;
}

?>