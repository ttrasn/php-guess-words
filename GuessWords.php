<?php

class GuessWords {

    private $continueStep = 0;
    public $mainLanguage = 'en-US';

    private function GetWords(){
        $data = file_get_contents(__DIR__."/words/".$this->mainLanguage."/all.json");
        return json_decode($data,true);
    }

    private function RelatedWords(){
        $data = file_get_contents(__DIR__."/words/".$this->mainLanguage."/related.json");
        return json_decode($data,true);
    }

    private function Numbers(){
        $data = file_get_contents(__DIR__."/words/".$this->mainLanguage."/numbers.json");
        return json_decode($data,true);
    }

    function GetText($content){
        $content = preg_split('//u', $content, null, PREG_SPLIT_NO_EMPTY);
        $new_content = '';
        $temp = $content[0];
        unset($content[0]);
        $is_eng = false;
        $reset = false;
        $related = $last_result = [];
        $numbers = $this->Numbers();
        while (true){
            foreach ($content as $key => $word){
                if ($reset) {
                    $reset = false;
                    break;
                }
                if($key < $this->continueStep){
                    continue;
                }
                if((!preg_match("/^[a-zA-Z\d <>?%\\/'\"=\[\]\-\(\)_:#.,،]+$/",$word)) && (!in_array($word,$numbers)) && !is_numeric($word)){
                    if($is_eng){
                        $new_content .= $temp.' ';
                        $temp = '';
                    }
                    $result = $this->GetWords();
                    $i = 0;
                    $find = 1;
                    while($find) {
                        $result = $this->RecursiveSearch($temp.$content[$key + $i],$result);
                        if(!count($result)){
                            if(isset($last_result[0])){
                                if(($last_result[0] != $temp) && isset($related['key'])){
                                    $this->continueStep = $related['key'];
                                    $new_content .= $related['word'].' ';
                                    $find = false;
                                    $temp = '';
                                    $related = [];
                                    $reset = true;
                                    continue;
                                }
                            }else{
                            }
                            $find = 0;
                            $this->continueStep = $key +$i;
                        }else{
                            $temp .= $content[$key +$i];
                            if(in_array($temp,$this->RelatedWords())){
                                $related['key'] = $key +$i + 1;
                                $related['word'] = $temp;
                            }
                            $last_result = array_values( $result);
                        }
                        $i++;
                    }
                    $related = [];
                    $new_content .= $temp.' ';
                    $temp = '';
                }else{
                    $is_eng = true;
                    if(in_array($word,['\'','"'])){
                        $temp .= $word.' ';
                    }else{
                        if(in_array($word,['a','i'])){
                            if($content[$key-1] == '<'){
                                if(($word == 'i') && ($content[$key+1] == 'm') && ($content[$key+2] == 'g')){
                                    $this->continueStep = $key +3;
                                    $word = 'img';
                                }
                                $temp .= $word.' ';
                                continue;
                            }
                        }
                        $temp .= $word;
                    }
                }
                if($key == count($content)){
                    break 2;

                }
            }
        }
        return $new_content;
    }

    private function RecursiveSearch($str,$array){
        $input = preg_quote($str, '~');
        $result = preg_grep('/^' . $input . '.*$/', $array);
        return $result;
    }
}