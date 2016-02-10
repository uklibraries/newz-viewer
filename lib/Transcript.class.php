<?php
class Transcript {
	private $fulltext;
	private $chunks;
	private $fulltextHTML;
	private $index;
	private $indexHTML;

	public function __construct($fulltext, $timecodes, $index) {
		$this->fulltext = (string)$fulltext;
		$this->index = $index;
		$this->chunks = $timecodes;
		$this->formatTranscript();
		$this->formatIndex();
	}

	public function getTranscriptHTML() {
		if (isset($this->fulltextHTML)) {
			return $this->fulltextHTML;
		}
	}

	public function getTranscript() {
		if (isset($this->fulltext)) {
			return $this->fulltext;
		}
	}

	public function getIndexHTML() {
		if (isset($this->indexHTML)) {
			return $this->indexHTML;
		}
	}

	private function formatIndex() {
		if (!empty($this->index)) {
			if (count($this->index->webpage) == 0) {
				$this->indexHTML = '';
				return;
			}
			$indexHTML = "<div id=\"accordionHolder\">\n";
			foreach ($this->index->webpage as $webpage) {
				$timePoint = (floor((int)$webpage->time / 60)) . ':' . str_pad(((int)$webpage->time % 60), 2, '0', STR_PAD_LEFT);
				$synopsis = $webpage->synopsis;
				$hypertext = $webpage->hypertext;
				$keywords = $webpage->keywords;
				$subjects = $webpage->subjects;
				$gps = $webpage->gps;
				$gps_text = $webpage->gps_text;
				$hyperlink = $webpage->hyperlink;
				$hyperlink_text = $webpage->hyperlink_text;

				$indexHTML .= '<h3><a href="#" id="link' . $webpage->time . '">' . trim($webpage->title, ';') . "</a></h3>\n";
				$indexHTML .= '<div class="webpage">' . "\n";
				$indexHTML .= '';
				$indexHTML .= '<p class="segmentLink" id="segmentLink' . $webpage->time . '"><strong>Direct segment link:</strong><br /><input type="text" readonly="readonly" class="segmentLinkTextBox" value="' . ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '#segment' . $webpage->time . '" /></p>';
				$indexHTML .= '<div class="synopsis">';
				$indexHTML .= '<a name="tp_' . $webpage->time . '"></a>';
				$indexHTML .= '<p><strong></strong><span>' . $hypertext . '</span></p><p><strong></strong><span> ' . $synopsis . '</span></p><p><strong></strong><span> ' . str_replace(';', '; ', $keywords) . '</span></p><p><strong></strong><span> ' . str_replace(';', ' ', $subjects) . '</span></p>';
				if ($gps <> '') {
					$indexHTML .= '<!--<br/><strong></strong> <a	class="fancybox-media" href="' . htmlentities(str_replace(' ', '', 'http://maps.google.com/maps?ll='.$gps.'&t=m&z=10&output=embed')).'">-->';
					if ($gps_text <> '') {
						$indexHTML .= $gps_text;
					}
					else {
						$indexHTML .= 'Link to map';
					}
					$indexHTML .= '</a><br/><strong></strong> ' . $gps .'<br/>';
				}
				if ($hyperlink <> '') {
					$indexHTML .= '<br/><strong></strong><br/> <img src="' . $hyperlink . '" width="200px"/><br/>';
				}
				$indexHTML .= '</div>';
				$indexHTML .= "\n</div>\n";
			}
			$this->indexHTML = $indexHTML . "</div>\n";
		}
	}

	private function formatTranscript() {
		$this->fulltextHTML = $this->fulltext;
		if (strlen($this->fulltextHTML) == 0) {
			return;
		}

		# quotes
		$this->fulltextHTML = preg_replace('/\"/', "&quot;", $this->fulltextHTML);

		# paragraphs
		$this->fulltextHTML = preg_replace('/Transcript: */',"",$this->fulltextHTML);

		# highlight kw

		# take timestamps out of running text
		$this->fulltextHTML = preg_replace("/{[0-9:]*}/","",$this->fulltextHTML);

		$this->fulltextHTML = preg_replace('/(.*)\n/msU',"<p>$1</p>\n",$this->fulltextHTML);

		# grab speakers
		$this->fulltextHTML = preg_replace('/<p>[[:space:]]*([A-Z-.\' ]+:)(.*)<\/p>/',"<p><span class=\"speaker\">$1</span>$2</p>",$this->fulltextHTML);

		$this->fulltextHTML = preg_replace('/<p>[[:space:]]*<\/p>/',"",$this->fulltextHTML);

		$this->fulltextHTML = preg_replace('/<\/p>\n<p>/ms',"\n",$this->fulltextHTML);

		$this->fulltextHTML = preg_replace('/<p>(.+)/U',"<p class=\"first-p\">$1",$this->fulltextHTML, 1);

		$chunkarray = explode(":",$this->chunks);
		$chunksize = $chunkarray[0];
		$chunklines =array();
		if (count($chunkarray)>1) {
			$chunkarray[1] = preg_replace('/\(.*?\)/',"",$chunkarray[1]);
			$chunklines = explode("|", $chunkarray[1]);
		}
		(empty($chunklines[0])) ? $chunklines[0] = 0 : array_unshift($chunklines, 0);

		# insert ALL anchors
		$itlines = explode("\n",$this->fulltextHTML);
		foreach ($chunklines as $key => $chunkline) {
			$stamp = $key*$chunksize . ":00";
			$itlines[$chunkline] = '<a href="#" data-timestamp="' . $key . '" data-chunksize="' . $chunksize . '" class="jumpLink">' . $stamp . '</a>' . $itlines[$chunkline];
		}

		$this->fulltextHTML = "";
		foreach ($itlines as $key => $line) {
			$this->fulltextHTML .= "<span class='fulltext-line' id='line_$key'>$line</span>\n";
		}
	}

	private function formatShortline($line, $keyword) {
		$shortline = preg_replace("/.*?\s*(\S*\s*)($keyword.*)/i","$1$2",$line);
		$shortline = preg_replace("/($keyword.{30,}?).*/i","$1",$shortline);
		$shortline = preg_replace("/($keyword.*\S)\s+\S*$/i","$1",$shortline);
		$shortline = preg_replace("/($keyword)/mis","<span class='highlight'>$1</span>",$shortline);
		$shortline = preg_replace('/\"/', "&quot;", $shortline);

		return $shortline;
	}

	private function quoteWords($string) {
		$q_kw = preg_replace('/\'/', '\\\'', $string);
		$q_kw = preg_replace('/\"/', "&quot;", $q_kw);
		return $q_kw;
	}

	private function quoteChange($string) {
		$q_kw = preg_replace('/\'/', "&#39;", $string);
		$q_kw = preg_replace('/\"/', "&quot;", $string);
		$q_kw = trim($q_kw);
		return $q_kw;
	}

	public function keywordSearch($keyword) {
		# quote kw for later
		$q_kw = $this->quoteWords($keyword);
		$json = "{ \"keyword\":\"$q_kw\", \"matches\":[";

		//Actual search
		$lines = explode("\n", $this->transcript);
		$totalLines = sizeof($lines);
		foreach ($lines as $lineNum => $line) {
			if (preg_match("/$keyword/i", $line, $matches)) {
				if ($lineNum < $totalLines-1) {
					$line .= ' ' . $lines[$lineNum + 1];
				}
				$shortline = $this->formatShortline($line, $keyword);
				if (strstr($json, 'shortline')) {
					$json .= ',';
				}
				$json .= "{ \"shortline\" : \"$shortline\", \"linenum\": $lineNum }";
			}
		}

		return str_replace("\0", "", $json) . ']}';
	}

	public function indexSearch($keyword) {
		if (!empty($keyword)){
			$q_kw = $this->quoteWords($keyword);
			$json = "{ \"keyword\":\"$q_kw\", \"matches\":[";

			foreach ($this->index->webpage as $webpage) {
				$synopsis = $webpage->synopsis;
				$keywords = $webpage->keywords;
				$subjects = $webpage->subjects;
				$time = $webpage->time;
				$title = $webpage->title;
				$timePoint = floor($time / 60) . ':' . str_pad($time % 60, 2, '0', STR_PAD_LEFT);

				if (preg_match("/{$keyword}/imsU", $synopsis) > 0
				|| preg_match("/{$keyword}/ismU", $title) > 0
				|| preg_match("/{$keyword}/ismU", $keywords) > 0
				|| preg_match("/{$keyword}/ismU", $subjects) > 0) {
					if (strstr($json, 'time')) {
						$json .= ', ';
					}
					$json .= '{ "time" :' . $time . ', "shortline" : "' . $timePoint . ' - ' . $this->quoteChange($title) . '" }';
				}
			}
		}

		return str_replace("\0", "", $json) . ']}';
	}
}
?>
