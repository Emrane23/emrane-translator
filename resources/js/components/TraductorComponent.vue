<template>
  <div class="wrapper">
    <ul class="languages">
      <li class="row from">
        <select v-model="translateFrom">
          <option v-for="(country, key) in countries" :key="key" :value="key">
            {{ country }}
          </option>
        </select>
      </li>
      <li class="exchange">
        <i
          title="Switch languages (Ctrl+Q)"
          :class="['fas', 'fa-exchange-alt', { clicked: isClicked }]"
          @click="reverseSelections()"
        ></i>
      </li>
      <li class="row to">
        <select v-model="translateTo" @change="translateFunction()">
          <option v-for="(country, key) in countries" :key="key" :value="key">
            {{ country }}
          </option>
        </select>
      </li>
    </ul>
    <hr />
    <div class="text-input">
      <textarea
        spellcheck="false"
        class="from-text"
        :placeholder="placeholder"
        v-model.debounce="text"
      ></textarea>
      <textarea
        spellcheck="false"
        readonly
        disabled
        class="to-text"
        placeholder="Translation"
        v-model="translatedText"
      ></textarea>
    </div>
    <div style="display: flex; flex-direction: row">
      <span class="textlength">{{ textLength }} / 5000</span>
      <div style="margin-left: 220px; position: absolute" v-if="detectOtherLanguage.language">
        <!-- <i class="fas fa-exclamation-circle"></i> -->
        <i class="fas fa-magic" style="color: gold;"></i>
         Langue source : <span class="detected" @click="changeToDetectedLanguage(detectOtherLanguage.keylanguage)">{{ detectOtherLanguage.language}}</span>
      </div>
      <div class="comment" v-if="errors">
        <i class="fas fa-exclamation-circle"></i>
        An error occurred in the server.
      </div>
      <div v-if="loading"  class="spinner-symbol spinner-border spinner-border-sm mt-2"></div>
    </div>
    <ul class="controls">
      <li class="row from">
        <div class="icons">
          <i
            v-if="textLength > 0"
            title="Listen"
            id="from"
            class="fas fa-volume-up " 
            :class="{ 'volume-on': isVolumeOn }" 
            :style="{ color : isVolumeOn ? 'blue' :'' }"
            @click="readOutLoud(text, translateFrom ,'from')"
          ></i>
          <i
            id="from"
            title="Translate with voice"
            class="fas fa-microphone"
            :class="{ animated: isRecording }"
            :style="{ color: isRecording ? 'red' : '' }"
            @click="recordingFunction()"
          ></i>
          <!-- <i id="from" class="fas fa-copy" @click="copyFunction('from')" :title="''"></i> -->
        </div>
      </li>
      <li class="row to">
        <div class="icons">
          <i
            id="to"
            class="fas fa-volume-up"
            title="Listen"
            v-if="translatedText.length > 0"
            :class="{ 'volume-on': isVolumeOnTo }" 
            :style="{ color : isVolumeOnTo ? 'blue' :'' }"
            @click="readOutLoud(translatedText, translateTo)"
          ></i>
          <i
            id="to"
            v-if="translatedText.length > 0 && !copied"
            class="fas fa-copy"
            title="Copy translation"
            @click="copyFunction('to')"
          >
          </i>
          <template v-if="copied">
            <i class="fas fa-check x-sm" style="color: green"></i>
          </template>
        </div>
      </li>
    </ul>
  </div>
  <!-- <button @click="translateFunction()">
    <i class="fas fa-language" aria-hidden="true"></i> Translate Text
  </button> -->
</template>

<script>
import allCountries from "../countries";
export default {
  data() {
    return {
      isClicked: false,
      isVolumeOn: false,
      isVolumeOnTo: false,
      isRecording: false,
      loading: false,
      detectOtherLanguage: {
        keylanguage:"",
        language:"",
      },
      errors:"",
      titleTo: "",
      titleFrom: "",
      placeholder: "Enter text",
      translateFrom: "fr",
      translateTo: "ar",
      copied: false,
      text: "",
      translatedText: "",
      countries: allCountries,
    };
  },
  watch: {
    text: function (query) {
      this.debouncedSearch(query);
    },
    translateFrom : function(query){
      this.translateFunction();
    }
  },
  created: function () {
    this.debouncedSearch = _.debounce(this.translateFunction, 500);
  },
  computed: {
    textLength() {
      return this.text.length;
    },
  },
  methods: {
    async translateFunction() {
      let { translateFrom, translateTo, text } = this;
      this.errors = "";
      if (text == "") {
        this.translatedText = "";
        return;
      }
      this.detectOtherLanguage = {
        keylanguage:"",
        language:"",
      };
      this.loading =true;
      await axios
        .post("/api/translate", { translateFrom, translateTo, text })
        .then((response) => {
          this.loading =false;
          this.translatedText = response.data.translatedtext;
          if (translateFrom != response.data.langDetcted) {
            for (const key in allCountries) {
                if (key == response.data.langDetcted) {
                  this.detectOtherLanguage.keylanguage = key;
                  this.detectOtherLanguage.language = allCountries[key];
                }
              }
          }
        })
        .catch(({ response }) => {
          this.loading =false;
          this.errors = "Sorry, an error occurred in the server!";
          this.translatedText = text;
        });
    },

    readOutLoud(message, target,otherTarget = "") {
      if (otherTarget == "") {
        this.isVolumeOnTo = !this.isVolumeOnTo;
        if (this.isVolumeOnTo == false) {
          window.speechSynthesis.cancel();
          return;
        }
        
      }else{
        this.isVolumeOn = !this.isVolumeOn;
        if (this.isVolumeOn == false) {
          window.speechSynthesis.cancel();
          return;
        }

      }
      var speech = new SpeechSynthesisUtterance();
      // Set the text and voice attributes.
      speech.text = message;
      speech.volume = 1;
      speech.rate = 1;
      speech.lang = target;
      speech.pitch = 1;
      window.speechSynthesis.speak(speech);
      speech.addEventListener('end', () => {
        this.isVolumeOn = false;
        this.isVolumeOnTo = false;
    });
    },

    copyFunction(target) {
      this.copied = true;
      this.$toast.show("The translation was well copied!",{
        position: 'bottom-left'
      });
      if (target == "from") {
        navigator.clipboard.writeText(this.text);
      } else {
        navigator.clipboard.writeText(this.translatedText);
      }
      setTimeout(() => {
        this.copied = false;
      }, 1500);
    },

    changeToDetectedLanguage(keylanguage){
      this.detectOtherLanguage = {
        keylanguage:"",
        language:"",
      };
      this.errors = "";
      this.translateFrom = keylanguage;
    },

    recordingFunction() {
      this.isRecording = !this.isRecording;
      window.SpeechRecognition =
        window.SpeechRecognition || window.webkitSpeechRecognition;
      const recognition = new window.SpeechRecognition();
      recognition.lang = this.translateFrom;
      recognition.continuous = true;

      // event current voice reco word
      recognition.addEventListener("result", (event) => {
        let text = Array.from(event.results)
          .map((result) => result[0])
          .map((result) => result.transcript)
          .join("");
        this.text = text;
      });
      // end of transcription
      recognition.addEventListener("end", () => {
        this.isRecording = false;
        recognition.stop();
      });
      recognition.start();
      if (this.isRecording == false) {
        recognition.stop();
        this.placeholder = "Enter text";
        return;
      }
      this.text = "";
      this.placeholder = "Speak now..";
    },



    reverseSelections() {
      let aux = "";
      let auxLang ="";
      this.isClicked = true;
      aux = this.translateFrom;
      auxLang = this.text ;
      this.translateFrom = this.translateTo;
      this.text = this.translatedText ;
      this.translateTo = aux;
      this.translatedText =auxLang;

      this.translateFunction();
      setTimeout(() => {
        this.isClicked = false;
      }, "500");
      
    },

    checkKeyCombination(event) {
      if (event.key === 'q' && event.ctrlKey ) {
        this.reverseSelections();
      }
    },
    
  },

  mounted() {
    document.addEventListener('keydown', this.checkKeyCombination);
  },

  beforeDestroy() {
    document.removeEventListener('keydown', this.checkKeyCombination);
  },
};
</script>

<style>
/* Import Google Font - Poppins */
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}
.animated {
  animation: pulse 1s ease-in-out infinite alternate;
}

@keyframes pulse {
  from {
    transform: scale(1);
  }
  to {
    transform: scale(1.2);
  }
}
body {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 10px;
  min-height: 100vh;
  /* background: #676a74; */
}
.fas {
  margin-right: 10px;
}


.container {
  max-width: 690px;
  width: 100%;
  padding: 30px;
  background: #fff;
  border-radius: 7px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
}
.fa-volume-up {
  transition: transform 0.3s ease;
}
.fa-volume-up.volume-on {
  transform: scale(1.2);
}
.wrapper {
  border-radius: 5px;
  border: 1px solid #ccc;
}
.wrapper .text-input {
  display: flex;
  border-bottom: 1px solid #ccc;
}
.text-input .to-text {
  border-radius: 0px;
  border-left: 1px solid #ccc;
}
.comment{
  margin-left: 220px; 
  color: red;
  position: absolute
}
.spinner-symbol{
  margin-left: 310px; 
  color: #5372f0;
  font-size: small;
  position: absolute
}
.text-input textarea {
  height: 250px;
  width: 100%;
  border: none;
  outline: none;
  resize: none;
  background: none;
  font-size: 18px;
  padding: 10px 15px;
  border-radius: 5px;
}

.text-input textarea::placeholder {
  color: #b7b6b6;
}
.languages {
  list-style: none;
  padding: 10px;
}

.languages,
li,
.icons,
.icons i {
  display: flex;
  align-items: center;
  justify-content: space-around;
}

.languages .row select {
  color: #333;
  border: none;
  outline: none;
  font-size: 18px;
  background: none;
  padding-left: 5px;
  max-width: 100%;
}
.textlength {
  margin-left: 10px;
  font-size: small;
}
.controls,
li,
.icons,
.icons i {
  display: flex;
  align-items: center;
  justify-content: space-around;
}
.controls {
  list-style: none;
  padding: 10px;
}
.controls .row .icons {
  width: 38%;
}
.controls .row .icons i {
  /* width: 50px; */
  color: #adadad;
  font-size: 14px;
  cursor: pointer;
}
.detected{
  color: #5372f0;
  cursor: pointer;
}
.controls .row.from .icons {
  padding-right: 15px;
  border-right: 1px solid #ccc;
}
.controls .row.to .icons {
  /* padding-left: 10px; */
  border-left: 1px solid #ccc;
}
.controls .row select {
  color: #333;
  border: none;
  outline: none;
  font-size: 18px;
  background: none;
  padding-left: 5px;
  max-width: 100%;
}
.text-input textarea::-webkit-scrollbar {
  width: 4px;
}
.controls .row select::-webkit-scrollbar {
  width: 8px;
}
.text-input textarea::-webkit-scrollbar-track,
.controls .row select::-webkit-scrollbar-track {
  background: #fff;
}
.text-input textarea::-webkit-scrollbar-thumb {
  background: #ddd;
  border-radius: 8px;
}
.controls .row select::-webkit-scrollbar-thumb {
  background: #999;
  border-radius: 8px;
  border-right: 2px solid #ffffff;
}
.languages .exchange {
  color: #5372f0;
  cursor: pointer;
  font-size: 16px;
  transition: transform 0.2s ease;
}
.controls i:active {
  transform: scale(0.9);
}
.container button {
  width: 100%;
  padding: 14px;
  outline: none;
  border: none;
  color: #fff;
  cursor: pointer;
  margin-top: 20px;
  font-size: 17px;
  border-radius: 5px;
  background: #5372f0;
}

.clicked {
  animation: shake 0.5s;
}

@keyframes shake {
  0% {
    transform: translate(0, 0);
  }
  25% {
    transform: translate(5px, 0);
  }
  50% {
    transform: translate(0, 0);
  }
  75% {
    transform: translate(-5px, 0);
  }
  100% {
    transform: translate(0, 0);
  }
}

@media (max-width: 660px) {
  .container {
    padding: 20px;
  }
  .wrapper .text-input {
    flex-direction: column;
  }
  .text-input .to-text {
    border-left: 0px;
    border-top: 1px solid #ccc;
  }
  .text-input textarea {
    height: 200px;
  }
  .controls .row .icons {
    display: none;
  }
  .container button {
    padding: 13px;
    font-size: 16px;
  }
  .controls .row select {
    font-size: 16px;
  }
  .controls .exchange {
    font-size: 14px;
  }
}
</style>
