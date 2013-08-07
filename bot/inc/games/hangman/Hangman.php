<?php

    class Hangman extends DAO
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
            $this->setTableName('t_hangman');
            $this->setPrimaryKey('fk_i_chat_id');
            $array_fields = array(
                'fk_i_chat_id',
                's_original_word',
                's_current_word',
                'i_status',
                's_scores'
            );
            $this->setFields($array_fields);
        }

        function startGame($chat, &$wa) {
            $status = Status::newInstance();
            $game = $status->findByPrimaryKey($chat);
            if(isset($game['s_game'])) {
                $status->update(
                    array(
                        's_game' => 'HANGMAN'
                        ,'s_status' => 'INGAME'
                    )
                    ,array('pk_i_id' => $chat)
                );
            } else {
                $status->insert(
                    array(
                        'pk_i_id' => $chat
                        ,'s_game' => 'HANGMAN'
                        ,'s_status' => 'INGAME'
                    )
                );
            }
            $original_word = strtoupper($this->randomWord());
            $firstChar = substr($original_word, 0, 1);
            $current_word = preg_replace('|([^'.$firstChar.'])|i', '-', $original_word);
            $this->insert(
                array(
                    'fk_i_chat_id' => $chat
                    ,'s_original_word' => $original_word
                    ,'s_current_word' => $current_word
                    ,'i_status' => 0
                    ,'s_scores' => json_encode(array())
                )
            );
            $wa->Message($chat, 'ACABA DE EMPEZAR UNA PARTIDA DE AHORCADO'.PHP_EOL.'LA PALABRA ES:'.PHP_EOL.$current_word);
            return $current_word;
        }

        public function sayChar($data, &$wa) {
            $game = $this->findByPrimaryKey($data['from']);
            $char = str_replace('Á', 'A',
                str_replace('É', 'E',
                    str_replace('Í', 'I',
                        str_replace('Ú', 'Ú',
                            str_replace('Ó', 'Ó', str_replace('Ü', 'U', strtoupper(substr($data['body'], 0, 1))))))));
            $gameProgress = $game['i_status'];
            $oword = $game['s_original_word'];
            $cword = $game['s_current_word'];

            $score = 0;
            if(strpos($game['s_original_word'], $char)!==false && strpos($game['s_current_word'], $char)===false) {
                // NEVER SAID BEFORE
                $l = strlen($oword);
                for($k=1;$k<$l;$k++) {
                    if($oword[$k]==$char) {
                        $cword[$k] = $char;
                        $score++;
                    }
                }
            } else {
                // INCORRECT LETTER OR ALREADY SAID
                if(strpos($game['s_current_word'], $char)!==false) {
                    $score = -2;
                } else {
                    $score = -1;
                }
                $gameProgress += 1;
            }

            $scores = json_decode($game['s_scores'], true);
            if(isset($scores[$data['author']])) {
                $scores[$data['author']]['score'] += $score;
            } else {
                $scores[$data['author']]['nickname'] = $data['nickname'];
                $scores[$data['author']]['score'] = $score;
            }

            $this->update(
                array(
                    's_current_word' => $cword
                    ,'i_status' => $gameProgress
                    ,'s_scores' => json_encode($scores)
                )
                ,array('fk_i_chat_id' => $data['from'])
            );

            if($oword==$cword) {
                $this->winGame($data['from'], $wa);
            } else {
                if($gameProgress>=7) {
                    $this->endGame($data['from'], $wa);
                } else {
                    if($score>0) {
                        $str = $data['nickname']." HA ACERTADO CON '".$data['body']."' : +".$score." puntos";
                    } else {
                        $str = $data['nickname']." HA FALLADO CON '".$data['body']."' : ".$score." puntos";
                    }
                    $wa->Message($data['from'], $str);
                    $wa->Message($data['from'], $this->asciiStatus($gameProgress));
                    $wa->Message($data['from'], $cword);
                }
            }

        }

        public function winGame($id, &$wa) {
            $game = $this->findByPrimaryKey($id);
            $str = "##1F388####1F388##FELICIDADES!##1F388####1F388## HAS GANADO, LA PALABRA ERA : ".$game['s_original_word'].'##1F388####1F388##'.PHP_EOL;
            $str .= $this->ranking($game);
            $wa->Message($id, $str);
            $this->resetData($id);
        }

        public function endGame($id, &$wa) {
            $game = $this->findByPrimaryKey($id);
            $this->asciiStatus($game['i_status']);
            $str = "##1F480####1F480##TE HAN COLGADO!##1F480####1F480## HAS PERDIDO, LA PALABRA ERA : ".$game['s_original_word'].'##1F480####1F480##'.PHP_EOL;
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

        public function randomWord() {
            $filename = dirname(__FILE__)."/words.txt";
            $lines = file($filename);
            $total_lines= count($lines)-1;
            return trim($lines[mt_rand(0,$total_lines)]);
        }

        public function asciiStatus($status = 0) {
            switch($status) {
                case 7:
                    return $str = '_______       '.PHP_EOL.
                        '|/      |     '.PHP_EOL.
                        '|     ##1F480##     '.PHP_EOL.
                        '|      \|/    '.PHP_EOL.
                        '|       |     '.PHP_EOL.
                        '|      / \    '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;
                case 6:
                    return $str = '_______       '.PHP_EOL.
                        '|/      |     '.PHP_EOL.
                        '|     ##1F468##     '.PHP_EOL.
                        '|      \|/    '.PHP_EOL.
                        '|       |     '.PHP_EOL.
                        '|      /      '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;
                case 5:
                    return $str = '_______       '.PHP_EOL.
                        '|/      |     '.PHP_EOL.
                        '|     ##1F468##     '.PHP_EOL.
                        '|      \|/    '.PHP_EOL.
                        '|       |     '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;
                case 4:
                    return $str = '_______       '.PHP_EOL.
                        '|/      |     '.PHP_EOL.
                        '|     ##1F468##     '.PHP_EOL.
                        '|      \|/    '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;
                case 3:
                    return $str = '_______       '.PHP_EOL.
                        '|/      |     '.PHP_EOL.
                        '|     ##1F468##     '.PHP_EOL.
                        '|      \|     '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;
                case 2:
                    return $str = '_______       '.PHP_EOL.
                        '|/      |     '.PHP_EOL.
                        '|     ##1F468##     '.PHP_EOL.
                        '|       |     '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;
                case 1:
                    return $str = '_______       '.PHP_EOL.
                        '|/      |     '.PHP_EOL.
                        '|     ##1F468##     '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;
                case 0:
                default:
                    return $str = '_______       '.PHP_EOL.
                        '|/            '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|             '.PHP_EOL.
                        '|___          ';
                    break;

            }
        }


    }
?>