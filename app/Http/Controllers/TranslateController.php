<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateController extends Controller
{
    /**
     * Translate a given text to sepecific language
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function translate(Request $request)
    {
        $translateFrom = $request->translateFrom;
        $tranlateTo = $request->translateTo;
        $text = $request->text;
        $tr = new GoogleTranslate($tranlateTo);
        $text = $tr->translate($text);
        $langDetcted = $tr->getLastDetectedSource();
        $translatedText = GoogleTranslate::trans($text, $tranlateTo, $translateFrom);
        return response()->json(['translatedtext' => $translatedText, 'langDetcted' => $langDetcted], 200);
    }
}
