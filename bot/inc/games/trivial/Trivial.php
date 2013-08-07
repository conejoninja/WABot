<?php

    class Trivial extends DAO
    {
        private static $instance;

        public static function newInstance()
        {
            if( !self::$instance instanceof self ) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        function __construct()
        {
            parent::__construct();
            $this->setTableName('t_trivial');
            $this->setPrimaryKey('fk_i_chat_id');
            $array_fields = array(
                'fk_i_chat_id',
                'i_question',
                'i_clue',
                's_topic',
                's_question',
                's_answer',
                's_scores',
                'dt_last_action'
            );
            $this->setFields($array_fields);
        }

        function startGame($chat, &$wa) {
            $status = Status::newInstance();
            $game = $status->findByPrimaryKey($chat);
            if(isset($game['s_game'])) {
                $status->update(
                    array(
                        's_game' => 'TRIVIAL'
                        ,'s_status' => 'INGAME'
                    )
                    ,array('pk_i_id' => $chat)
                );
            } else {
                $status->insert(
                    array(
                        'pk_i_id' => $chat
                        ,'s_game' => 'TRIVIAL'
                        ,'s_status' => 'INGAME'
                    )
                );
            }
            $question = $this->randomQuestion();
            $game = array(
                'fk_i_chat_id' => $chat
                ,'i_question' => 0
                ,'i_clue' => 0
                ,'s_topic' => $question['topic']
                ,'s_question' => $question['text']
                ,'s_answer' => $question['answer']
                ,'s_scores' => json_encode(array())
                ,'dt_last_action' => date('Y-m-d H:i:s')
            );
            $this->insert(array(
                'fk_i_chat_id' => $chat
            ,'i_question' => 0
            ,'i_clue' => 0
            ,'s_topic' => $question['topic']
            ,'s_question' => $question['text']
            ,'s_answer' => $question['answer']
            ,'s_scores' => json_encode(array())
            ,'dt_last_action' => date('Y-m-d H:i:s')));
            print_r($game);
            $wa->Message($chat, 'ACABA DE EMPEZAR UNA PARTIDA DE TRIVIAL'.PHP_EOL);
            $this->showQuestion($game, $wa);
            return $question;
        }

        public function sayWord($data, &$wa) {
            $game = $this->findByPrimaryKey($data['from']);
            $word = str_replace('Á', 'A',
                str_replace('É', 'E',
                    str_replace('Í', 'I',
                        str_replace('Ú', 'Ú',
                            str_replace('Ó', 'Ó', str_replace('Ü', 'U', strtoupper($data['body'])))))));
            $answer = str_replace('Á', 'A',
                str_replace('É', 'E',
                    str_replace('Í', 'I',
                        str_replace('Ú', 'Ú',
                            str_replace('Ó', 'Ó', str_replace('Ü', 'U', strtoupper($game['s_answer'])))))));

            if($word==$answer) {
                // SOMEONE GUESS THE ANSWER!
                if($game['i_clue']==0) {
                    $score = 10;
                } else if($game['i_clue']==0) {
                    $score = 5;
                } else {
                    $score = 3;
                }
            } else {
                // SOMEONE FAILED!
                $score = -1;

            }

            $scores = json_decode($game['s_scores'], true);
            if(isset($scores[$data['author']])) {
                $scores[$data['author']]['score'] += $score;
            } else {
                $scores[$data['author']]['nickname'] = $data['nickname'];
                $scores[$data['author']]['score'] = $score;
            }

            $this->update(
                array('s_scores' => json_encode($scores))
                ,array('fk_i_chat_id' => $data['from'])
            );

            if($score>0) {
                $str = $data['nickname']." HA ACERTADO CON '".$data['body']."' : +".$score." puntos";
                $wa->Message($data['from'], $str);
                $this->nextQuestion($game, $wa);
            } else {
                $str = $data['nickname']." HA FALLADO CON '".$data['body']."' : ".$score." puntos";
                $wa->Message($data['from'], $str);
            }



        }

        public function nextQuestion($game, &$wa) {
            $gameProgress = $game['i_question']+1;
            if($gameProgress>=10) {
                $this->endGame($game['fk_i_chat_id'], $wa);
            } else {
                $question = $this->randomQuestion();
                $newQuestion = array(
                    'i_question' => $gameProgress
                    ,'i_clue' => 0
                    ,'s_topic' => $question['topic']
                    ,'s_question' => $question['text']
                    ,'s_answer' => $question['answer']
                );
                $this->update(
                    $newQuestion
                    ,array('fk_i_chat_id' => $game['fk_i_chat_id'])
                );
                $newQuestion['fk_i_chat_id'] = $game['fk_i_chat_id'];
                $this->showQuestion($newQuestion, $wa);
            }
        }

        public function endGame($id, &$wa) {
            $game = $this->findByPrimaryKey($id);
            $str = "##1F388####1F388##EL TRIVIAL HA TERMINADO!##1F388####1F388##".PHP_EOL;
            $str .= $this->ranking($game);
            $wa->Message($id, $str);
            $this->resetData($id);
        }

        public function ranking($game) {
            $scores = json_decode($game['s_scores'], true);
            $str = "RANKING DE JUGADORES:".PHP_EOL;
            $players = $this->sortScores(json_decode($game['s_scores'], true));
            $l = count($players);
            for($k=0;$k<$l;$k++) {
                $str .= ($k+1).". ".$players[$k]['nickname']. "(".$players[$k]['score']." pts)".PHP_EOL;
            }
            return $str;
        }

        public function sortScores($scores) {
            usort($scores, function($a, $b) {
                return $b['score'] - $a['score'];
            });
            return $scores;
        }

        public function resetData($id) {
            $this->deleteByPrimaryKey($id);
            Status::newInstance()->deleteByPrimaryKey($id);
        }

        public function randomQuestion() {
            $filename = dirname(__FILE__)."/questions/miscelanea.txt";
            $lines = file($filename);
            $total_lines= count($lines)-1;
            if(preg_match('|^([^©]*)©([^«]*)«([^\*]*)\*(.*)$|', trim($lines[mt_rand(0,$total_lines)]), $match)) {
                $question = array();
                $question['topic'] = $match[1];
                $question['text'] = $match[3];
                $question['answer'] = $match[4];
                return $question;
            }
            return $this->randomQuestion();
        }

        public function showQuestion($game, &$wa) {
            $wa->Message($game['fk_i_chat_id'], "PREGUNTA ".($game['i_question']+1)." :".PHP_EOL.$game['s_topic'].' - '.$game['s_question']);
            $this->showClue($game, $wa);
        }

        public function showClue($game, &$wa, $clue = 0) {
            switch($clue) {
                case 2:
                    $s_clue = preg_replace('|([^aeiouA-Z ])|', '-', ucwords(strtolower($game['s_answer'])));
                    break;
                case 1:
                    $s_clue = preg_replace('|([^A-Z ])|', '-', ucwords(strtolower($game['s_answer'])));
                    break;
                case 0:
                default:
                    $s_clue = preg_replace('|([^ ])|', '-', $game['s_answer']);
                    break;
            }
            $this->update(array('i_clue' => $clue, 'dt_last_action' => date('Y-m-d H:i:s')), array('fk_i_chat_id' => $game['fk_i_chat_id']));
            $wa->Message($game['fk_i_chat_id'], 'PISTA '.($clue+1).': '.strtoupper($s_clue));
        }

        public function doSomething(&$wa) {
            $this->dao->select('*');
            $this->dao->from($this->getTableName());
            $this->dao->where('TIMESTAMPDIFF(SECOND,dt_last_action,\''.date('Y-m-d H:i:s').'\') >= 20');

            $result = $this->dao->get();

            if( $result == false ) {
                return array();
            }

            $games = $result->result();

            foreach($games as $game) {
                $clue = $game['i_clue']+1;
                if($clue>=3) {
                    $wa->Message($game['fk_i_chat_id'], 'NADIE HA ACERTADO, LA RESPUESTA ERA: '.$game['s_answer']);
                    $this->nextQuestion($game, $wa);
                } else {
                    $this->showClue($game, $wa, $clue);
                }
            }

        }

    }
?>