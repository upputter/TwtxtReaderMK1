<?php
use Twtxt\Parsers as Parsers;
use Twtxt\TwtxtParsedown;

Class TwtxtEntry {

    public $hash;      
    public $replyTo;
    public $type = 'post';
    protected $parsedMessageString = '';
    protected bool $messageIsFullyParsed = false;
    public $message = '';
    public $displayDateTime;
    public $localTime;
    public $timeAgo = ['value' => 0, 'unit' => 'translationKey'];
    protected $markDownSafeMode = false;
    public array $conversation = [];
    public int $conversationLevel = 0;
    public bool $isSubentry = false;    
    public array $conversationEntries = [];
        
    public function __construct(
        public DateTime $dateTime,        
        public string $nick,
        public string $rawMessageLine,
        public string $rawMessage,
        public string $url,
        public string $avatarUrl = '',
    )
    {
        $this->hash = $this->calculateHash();
        // add Twtxt line breaks
        $this->rawMessageLine = mb_ereg_replace("\u{2028}", "\n", $this->rawMessageLine);
        $this->rawMessage = mb_ereg_replace("\u{2028}", "\n", $this->rawMessage);

        // first parsing of the message (reduce load)
        $this->parsedMessageString = $this->parseMessageFirstStage();
        
        $this->displayDateTime = $this->dateTime;      
        $this->displayDateTime->setTimeZone(new DateTimeZone('Europe/Berlin'));

        // $this->localTime = $this->dateTime;
        // var_dump($this->localTime->format('U')); die();
        // var_dump($this->localTime->getTimezone()->getName()); die();
        
        // build conversation
        $this->conversation[] = $this->hash;
        if ($this->replyTo) $this->conversation[] = $this->replyTo;
    }

    // get-function for Fluid
    public function getConversation() {
        return array_unique($this->conversation);
    }

    public function setConversationEntries(string $hash, $data) {
        $data->isSubentry = true;
        $this->conversationEntries[$hash] = $data;       
        @usort($this->conversationEntries, function($a, $b) { return ($a->dateTime >= $b->dateTime) ? true : false;});
    }

    protected function parseMessageFirstStage() {
        $string = $this->rawMessage;
        $string = $this->parseReplyHash($string);
        $this->parseTimeAgo();
        return $string;
    }

    public function getMessage() {
        if (!$this->messageIsFullyParsed) { // we need to parse the message
            $string = $this->parsedMessageString; // get preparsed message (reply)

            // execute parsers from /lib/TwtxtParser/*.php
            $parsers = [
                'Youtube', 
                'iFrameVideo',
                'ImageLinkToMarkdown',
                'VideoLink',
                'HtmlEntities', 
                'MaskHashtags',
                'TwtxtMention',
            ];

            foreach ($parsers AS $parser) {
                $string = call_user_func('Twtxt\\Parsers\\' . $parser .'::parse', $string ); // execute parse-function for each twtxt-parser
            }

            $this->parsedMessageString = $string; // store the fully parsed message
            $this->messageIsFullyParsed = true; // set flag, so w don't parse again
            // Markdown with Parsedown
            // $pd = new Parsedown();
            $pd = new TwtxtParsedown();
            $pd->setSafeMode($this->markDownSafeMode);
            $pd->setBreaksEnabled(true);
            $pd->setMarkupEscaped($this->markDownSafeMode);
            
            $this->message = $pd->text($this->parsedMessageString);            
        }
        return $this->message;
    }

    protected function parseReplyHash($string) {
        $pattern = '/\(#([^\)]+)\)/'; // Matches "(#<text>)"
	    if (preg_match($pattern, $this->rawMessage, $matches)) {
            if (mb_strlen($matches[1]) == 7) {
                $this->replyTo = $matches[1];                
                $string = mb_ereg_replace('\(#' . $matches[1]. '\)', '', $string);
                $this->type = 'reply';
            }
        }
        return $string;
    }

    protected function parseTimeAgo()
    {
        $time = $this->dateTime->format('U');
        $time_difference = time() - $time;        
        if( $time_difference < 1 ) $time_difference = 1;
        
        $condition = array( 12 * 30 * 24 * 60 * 60 =>  'year',
                    30 * 24 * 60 * 60       =>  'month',
                    24 * 60 * 60            =>  'day',
                    60 * 60                 =>  'hour',
                    60                      =>  'minute',
                    1                       =>  'second'
        );

        foreach( $condition as $secs => $str ) {
            $delta = abs($time_difference / $secs);
            if( $delta >= 1 ) {
                $t = round( $delta );
                $this->timeAgo = [
                    'value' => $t, 
                    'unit' => $str . ( $t > 1 ? 's' : '' )];
                break;
            }
        }        
    }

    protected function calculateHash() {
		// date_default_timezone_set('UTC');
        $dt = $this->dateTime;
        // $dt->setTimezone(new DateTimeZone('UTC'));
		$dateStr = $dt->format(DateTime::RFC3339);
		$dateStr = str_replace('+00:00', 'Z', $dateStr);
		$dateStr = str_replace('-00:00', 'Z', $dateStr);
		// $dateStr = str_replace('.000000', '', $dateStr);
        $hashPayload = implode("\n", [$this->url, $dateStr, $this->rawMessage]);
        $hashBytes = sodium_crypto_generichash($hashPayload);
		$hashStr = substr(Base32::encode($hashBytes), -7);
		return $hashStr;
    }
}