#!/usr/bin/php
<?php

require_once 'config.php';

require_once '../whatsapi/src/php/whatsprot.class.php';
require_once 'inc/utils.php';

require_once 'inc/database/DBConnectionClass.php';
require_once 'inc/database/DBCommandClass.php';
require_once 'inc/database/DBRecordsetClass.php';
require_once 'inc/database/DAO.php';
require_once 'inc/database/Log.php';
require_once 'inc/database/Status.php';

require_once 'inc/games/hangman/Hangman.php';
require_once 'inc/games/trivial/Trivial.php';


function fgets_u($pStdn)
{
    $pArr = array($pStdn);

    if (false === ($num_changed_streams = stream_select($pArr, $write = NULL, $except = NULL, 0))) {
        print("\$ 001 Socket Error : UNABLE TO WATCH STDIN.\n");

        return FALSE;
    } elseif ($num_changed_streams > 0) {
        return trim(fgets($pStdn, 1024));
    }
}


echo "[] Logging in as '".WA_NICKNAME."' (".WA_SENDER.")\n";
$wa = new WhatsProt(WA_SENDER, WA_IMEI, WA_NICKNAME, FALSE);

$wa->Connect();
$wa->LoginWithPassword(WA_PASSWORD);


$status = Status::newInstance();

while (TRUE) {
    $wa->PollMessages();
    $data = $wa->GetMessages();
    $msgs = processMessages($data);
    if(!empty($msgs)) {
        foreach($msgs as $m) {
            print_r($m);
            $firstChar = substr($m['body'],0,1);
            if($firstChar=='/') { // IT'S A COMMAND!
                if(strtolower($m['body'])=='/ahorcado') {
                    $game = $status->findByPrimaryKey($m['from']);
                    if(isset($game['s_game']) && $game['s_game']!='') {
                        if($game['s_game']!='HANGMAN') {
                            $wa->Message($m['from'], 'Ya se encuentra jugando una partida de '.$game['s_game']);
                        }
                    } else {
                        Hangman::newInstance()->startGame($m['from'], $wa);
                    }
                } else if(strtolower($m['body'])=='/trivial') {
                    $game = $status->findByPrimaryKey($m['from']);
                    if(isset($game['s_game']) && $game['s_game']!='') {
                        if($game['s_game']!='TRIVIAL') {
                            $wa->Message($m['from'], 'Ya se encuentra jugando una partida de '.$game['s_game']);
                        }
                    } else {
                        Trivial::newInstance()->startGame($m['from'], $wa);
                    }
                }
            } else {
                $game = $status->findByPrimaryKey($m['from']);
                if($game['s_game']=='HANGMAN' && $game['s_status']=='INGAME') {
                    if(strlen($m['body'])==1) {
                        Hangman::newInstance()->sayChar($m, $wa);
                    }
                } else if($game['s_game']=='TRIVIAL' && $game['s_status']=='INGAME') {
                    Trivial::newInstance()->sayWord($m, $wa);
                }
            }
        }
    }
    Trivial::newInstance()->doSomething($wa);
    sleep(2);
}



?>
