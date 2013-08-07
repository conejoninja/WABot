
CREATE TABLE IF NOT EXISTS `t_hangman` (
  `fk_i_chat_id` varchar(35) NOT NULL,
  `s_original_word` varchar(100) NOT NULL,
  `s_current_word` varchar(100) NOT NULL,
  `i_status` int(11) NOT NULL,
  `s_scores` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `t_scores` (
  `fk_i_chat_id` varchar(35) NOT NULL,
  `s_game` varchar(20) NOT NULL,
  `i_wins` int(11) NOT NULL,
  `i_loses` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



CREATE TABLE IF NOT EXISTS `t_status` (
  `pk_i_id` varchar(35) NOT NULL,
  `s_game` varchar(20) NOT NULL,
  `s_status` varchar(20) NOT NULL,
  PRIMARY KEY (`pk_i_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `t_log` (
  `fk_i_chat_id` varchar(35) NOT NULL,
  `s_author` varchar(35) NOT NULL,
  `s_id` varchar(14) NOT NULL,
  `s_nickname` varchar(100) NOT NULL,
  `i_timestamp` varchar(10) NOT NULL,
  `s_message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `t_trivial` (
  `fk_i_chat_id` varchar(35) NOT NULL,
  `i_question` int(11) NOT NULL,
  `i_clue` int(11) NOT NULL,
  `s_topic` varchar(100) NOT NULL,
  `s_question` varchar(255) NOT NULL,
  `s_answer` varchar(100) NOT NULL,
  `s_scores` text NOT NULL,
  `dt_last_action` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

