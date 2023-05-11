<template>
  <div class="wrapper">
    <ul class="languages">
      <li class="row from">
        <select v-model="translateFrom" @change="translateFunction()">
          <option v-for="(country, key) in countries" :key="key" :value="key">
            {{ country }}
          </option>
        </select>
      </li>
      <li class="exchange">
        <i
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
      <div style="margin-left: 220px; position: absolute">
        Langue source : <span class="detected" @click="changeToDetectedLanguage()">Anglais</span>
      </div>
    </div>
    <ul class="controls">
      <li class="row from">
        <div class="icons">
          <i
            id="from"
            class="fas fa-volume-up " 
            :class="{ 'volume-on': isVolumeOn }" 
            :style="{ color : isVolumeOn ? 'blue' :'' }"
            @click="readOutLoud(text, translateFrom ,'from')"
          ></i>
          <i
            id="from"
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
            :class="{ 'volume-on': isVolumeOnTo }" 
            :style="{ color : isVolumeOnTo ? 'blue' :'' }"
            @click="readOutLoud(translatedText, translateTo)"
          ></i>
          <i
            id="to"
            class="fas fa-copy"
            @click="copyFunction('to')"
            v-if="!copied"
          >
          </i>
          <template v-else>
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
      if (text == "") {
        this.translatedText = "";
        return;
      }
      await axios
        .post("/api/translate", { translateFrom, translateTo, text })
        .then((response) => {
          this.translatedText = response.data.translatedtext;
        })
        .catch(({ response }) => {
          console.log(response.data.errors);
        });
    },

    readOutLoud(message, target,otherTarget = "") {
      console.log(otherTarget);
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
      if (target == "from") {
        navigator.clipboard.writeText(this.text);
      } else {
        navigator.clipboard.writeText(this.translatedText);
      }
      setTimeout(() => {
        this.copied = false;
      }, 1500);
    },

    changeToDetectedLanguage(){
      console.log("do!");
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
      this.isClicked = true;
      aux = this.translateFrom;
      this.translateFrom = this.translateTo;
      this.translateTo = aux;
      setTimeout(() => {
        this.isClicked = false;
      }, "500");
    },
  },

  mounted() {},
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
