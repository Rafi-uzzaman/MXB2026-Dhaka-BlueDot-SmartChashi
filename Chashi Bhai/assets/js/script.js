var isVoice = 0;
var isVoiceConversation = false; // Track if current conversation was initiated by voice
const DEFAULT_VOICE_NAME = 'Flo-en-US';
const PREFERRED_VOICE_ALIASES = [
    'Flo-en-US',
    'Flo (en-US)',
    'Flo en-US',
    'Flo US',
    'Flo'
];
const VOICE_STORAGE_KEY = 'preferredVoiceName';
const LANG_STORAGE_KEY = 'preferredLangCode';

let silenceInterval = null;

function startSilence() {
    stopSilence(); // Clear any existing interval
    silenceInterval = setInterval(() => {
        if (!window.speechSynthesis.speaking) {
            const utterance = new SpeechSynthesisUtterance(" ");
            utterance.volume = 0;
            window.speechSynthesis.speak(utterance);
        }
    }, 10000); // every 10 seconds
}

function stopSilence() {
    if (silenceInterval) {
        clearInterval(silenceInterval);
        silenceInterval = null;
    }
}

function stopSpeech() {
    const speechEngine = window.speechSynthesis;
    if (speechEngine.speaking) {
        speechEngine.cancel();
    }
}

// Simple voice response function - reads response in original language
function speakAIResponse(text, detectedLang, alwaysSpeak = false) {
    console.log('🔊 speakAIResponse called with:', {
        textPreview: text?.substring(0, 100),
        detectedLang: detectedLang,
        alwaysSpeak: alwaysSpeak,
        isVoiceConversation: isVoiceConversation,
        textHasBengali: /[\u0980-\u09FF]/.test(text)
    });

    // Check if speech synthesis is supported
    if (!('speechSynthesis' in window)) {
        console.error('❌ Speech synthesis not supported');
        return;
    }

    // Only speak if voice conversation is active or always speak is enabled
    const shouldSpeak = isVoiceConversation || alwaysSpeak || localStorage.getItem('alwaysSpeak') === 'true';
    if (!shouldSpeak) {
        console.log('🔇 Not speaking - conditions not met');
        return;
    }

    const synth = window.speechSynthesis;

    // Clean text for speech
    let cleanText = text
        .replace(/<[^>]*>/g, ' ')
        .replace(/\*\*([^*]+)\*\*/g, '$1')
        .replace(/\*([^*]+)\*/g, '$1')
        .replace(/\s+/g, ' ')
        .trim();

    if (!cleanText) {
        console.warn('⚠️ No text to speak');
        return;
    }

    // Stop any current speech
    synth.cancel();

    // Get available voices
    let voices = synth.getVoices();
    
    // If voices array is empty, try to trigger loading
    if (voices.length === 0) {
        console.log('⏳ No voices loaded yet, attempting to trigger load...');
        // Trigger voice loading by speaking empty utterance
        const triggerUtterance = new SpeechSynthesisUtterance('');
        synth.speak(triggerUtterance);
        synth.cancel();
        
        // Try getting voices again
        voices = synth.getVoices();
        
        if (voices.length === 0) {
            console.warn('⚠️ No voices available after trigger attempt');
            // Set a one-time listener for when voices load
            if (typeof synth.onvoiceschanged !== 'undefined') {
                const onceHandler = () => {
                    synth.onvoiceschanged = null;
                    const newVoices = synth.getVoices();
                    if (newVoices.length > 0) {
                        console.log('✅ Voices loaded, speaking now');
                        speakAIResponse(text, detectedLang, alwaysSpeak);
                    }
                };
                synth.onvoiceschanged = onceHandler;
                // Timeout to prevent waiting forever
                setTimeout(() => {
                    if (synth.onvoiceschanged === onceHandler) {
                        synth.onvoiceschanged = null;
                        console.error('❌ Voices failed to load within timeout');
                    }
                }, 2000);
            }
            return;
        }
    }
    
    console.log('🎤 Available voices:', voices.length, 'Detected language:', detectedLang);
    
    // Language mapping: convert short codes to full locale codes for speech synthesis
    const languageMap = {
        'bn': 'bn-BD',    // Bengali (Bangladesh)
        'BN': 'bn-BD',    // Bengali uppercase
        'hi': 'hi-IN',    // Hindi (India) 
        'HI': 'hi-IN',
        'mr': 'mr-IN',    // Marathi (India)
        'ur': 'ur-PK',    // Urdu (Pakistan)
        'ta': 'ta-IN',    // Tamil (India)
        'te': 'te-IN',    // Telugu (India)
        'gu': 'gu-IN',    // Gujarati (India)
        'kn': 'kn-IN',    // Kannada (India)
        'ml': 'ml-IN',    // Malayalam (India)
        'pa': 'pa-IN',    // Punjabi (India)
        'as': 'as-IN',    // Assamese (India)
        'or': 'or-IN',    // Odia (India)
        'en': 'en-US',    // English (US)
        'EN': 'en-US',    // English uppercase
        'ar': 'ar-SA',    // Arabic (Saudi Arabia)
        'zh': 'zh-CN',    // Chinese (China)
        'es': 'es-ES',    // Spanish (Spain)
        'fr': 'fr-FR',    // French (France)
        'de': 'de-DE',    // German (Germany)
        'it': 'it-IT',    // Italian (Italy)
        'pt': 'pt-PT',    // Portuguese (Portugal)
        'ru': 'ru-RU',    // Russian (Russia)
        'ja': 'ja-JP',    // Japanese (Japan)
        'ko': 'ko-KR',    // Korean (Korea)
        'th': 'th-TH',    // Thai (Thailand)
        'vi': 'vi-VN',    // Vietnamese (Vietnam)
        'id': 'id-ID',    // Indonesian (Indonesia)
        'ms': 'ms-MY',    // Malay (Malaysia)
        'tl': 'tl-PH',    // Filipino (Philippines)
        'my': 'my-MM',    // Myanmar (Myanmar)
        'km': 'km-KH',    // Khmer (Cambodia)
        'lo': 'lo-LA',    // Lao (Laos)
        'si': 'si-LK',    // Sinhala (Sri Lanka)
        'ne': 'ne-NP'     // Nepali (Nepal)
    };
    
    // PRIORITY: Use detected language from response, with intelligent detection
    let targetLang = detectedLang;
    
    // If detectedLang is not provided or is default, try to auto-detect from text content
    if (!targetLang || targetLang === 'EN' || targetLang === 'en' || targetLang === 'unknown') {
        // Check if text contains Bengali characters
        if (/[\u0980-\u09FF]/.test(cleanText)) {
            targetLang = 'bn';
            console.log('🔍 Auto-detected Bengali from text content');
        } else if (/[\u0900-\u097F]/.test(cleanText)) {
            targetLang = 'hi';
            console.log('🔍 Auto-detected Hindi from text content');
        } else if (/[\u0600-\u06FF]/.test(cleanText)) {
            targetLang = 'ar';
            console.log('🔍 Auto-detected Arabic from text content');
        } else {
            targetLang = 'en';
        }
    }
    
    // Convert short language code to full locale if needed
    if (targetLang && languageMap[targetLang]) {
        const originalLang = targetLang;
        targetLang = languageMap[targetLang];
        console.log('🔄 Mapped language:', originalLang, '->', targetLang);
    } else if (targetLang && !targetLang.includes('-')) {
        // If no mapping found, try lowercase
        const lowerLang = targetLang.toLowerCase();
        if (languageMap[lowerLang]) {
            targetLang = languageMap[lowerLang];
            console.log('🔄 Mapped language (lowercase):', lowerLang, '->', targetLang);
        }
    }
    
    console.log('🌍 Target language for speech:', targetLang, '(Original detectedLang:', detectedLang, ')');
    
    // Enhanced voice selection with better language matching and Bengali support
    let voice = null;
    let actualLang = targetLang;
    
    // Enhanced Bengali voice handling for all devices
    if (targetLang === 'bn-BD' || targetLang.startsWith('bn')) {
        console.log('🔍 Searching for Bengali voices...');
        
        // Get all Bengali-related voices
        const bengaliVoices = voices.filter(v => {
            const lang = (v.lang || '').toLowerCase();
            const name = (v.name || '').toLowerCase();
            return lang.startsWith('bn') || 
                   lang.includes('bengali') ||
                   name.includes('bengali') || 
                   name.includes('bangla') ||
                   name.includes('bangladesh') ||
                   name.includes('বাংলা');
        });
        
        if (bengaliVoices.length > 0) {
            console.log(`✅ Found ${bengaliVoices.length} Bengali voice(s):`, 
                bengaliVoices.map(v => `${v.name} (${v.lang})`));
            
            // Score and select best Bengali voice
            const scoredVoices = bengaliVoices.map(v => {
                const name = v.name.toLowerCase();
                const lang = v.lang.toLowerCase();
                let score = 0;
                
                // Google voices get highest priority (best quality)
                if (name.includes('google')) score += 100;
                // Microsoft voices second priority
                if (name.includes('microsoft')) score += 50;
                // Exact language match
                if (lang === 'bn-bd') score += 30;
                if (lang === 'bn-in') score += 20;
                if (lang === 'bn') score += 25;
                // Natural/enhanced voices
                if (name.includes('natural') || name.includes('enhanced')) score += 10;
                
                return { voice: v, score };
            });
            
            // Select highest scoring voice
            scoredVoices.sort((a, b) => b.score - a.score);
            voice = scoredVoices[0].voice;
            actualLang = voice.lang || 'bn-BD';
            
            console.log('🎯 Selected Bengali voice:', voice.name, '(', voice.lang, ') - Score:', scoredVoices[0].score);
        } else {
            console.warn('⚠️ No Bengali voice found in system');
            console.log('Available languages:', [...new Set(voices.map(v => v.lang))].sort());
            
            // Show user-friendly message
            console.log('💡 Tip: Bengali text will be displayed but cannot be spoken without Bengali voice');
        }
    }
    
    // General voice selection if Bengali not found or other languages
    if (!voice) {
        // Try exact language match first (e.g., 'hi-IN')
        voice = voices.find(v => v.lang === targetLang);
        if (voice) {
            console.log('✅ Found exact language match:', voice.name, voice.lang);
            actualLang = voice.lang;
        } else {
            // Try language family match (e.g., 'hi' from 'hi-IN')
            const langCode = targetLang.split('-')[0];
            voice = voices.find(v => v.lang.startsWith(langCode));
            if (voice) {
                console.log('✅ Found language family match:', voice.name, voice.lang);
                actualLang = voice.lang;
            } else {
                // Try any available voice - the browser will do its best to render the text
                // Even without a perfect language match, modern TTS can handle Unicode text
                voice = voices.find(v => v.lang.startsWith('en')) || voices[0];
                
                if (voice) {
                    console.log('⚠️ No exact language match, using available voice:', voice.name, voice.lang);
                    // Keep targetLang for the utterance even if voice doesn't match
                    // This tells the browser what language the text is in
                    actualLang = targetLang; 
                } else {
                    console.error('❌ No voices available at all');
                    return;
                }
            }
        }
    }

    if (!voice) {
        console.error('❌ No voice available for speech synthesis');
        return;
    }

    // Create utterance with optimized settings for different languages
    const utterance = new SpeechSynthesisUtterance(cleanText);
    // Set lang to target language (what the text is) not voice language (what reads it)
    utterance.lang = targetLang;
    utterance.voice = voice;
    
    console.log('🎯 Speech utterance config:', {
        text: cleanText.substring(0, 50) + '...',
        targetLang: targetLang,
        voiceName: voice?.name,
        voiceLang: voice?.lang
    });
    
    // Language-specific optimizations
    if (actualLang.startsWith('bn')) {
        // Bengali voice optimization - tuned for Google Bengali voice
        utterance.rate = 0.85; // Slightly slower for clarity
        utterance.pitch = 1.0;
        utterance.volume = 1.0;
        
        // For Google voices, we can use slightly faster rate
        if (voice.name.toLowerCase().includes('google')) {
            utterance.rate = 0.9; // Google Bengali handles speed better
        }
        
        console.log('🎯 Bengali voice optimization applied:', {
            voice: voice.name,
            rate: utterance.rate,
            provider: voice.name.toLowerCase().includes('google') ? 'Google' : 
                     voice.name.toLowerCase().includes('microsoft') ? 'Microsoft' : 'Other'
        });
    } else if (actualLang.startsWith('hi')) {
        // Hindi voice optimization
        utterance.rate = 0.85;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;
    } else if (actualLang.startsWith('en')) {
        // English voice optimization
        utterance.rate = 0.9;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;
    } else {
        // Default settings for other languages
        utterance.rate = 0.85;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;
    }
    
    console.log('🎙️ Speech setup:', {
        text: cleanText.substring(0, 100) + '...',
        textLength: cleanText.length,
        targetLang: targetLang,
        actualLang: actualLang,
        voiceName: voice.name,
        isBengali: /[\u0980-\u09FF]/.test(cleanText),
        firstChars: cleanText.substring(0, 20).split('').map(c => c.charCodeAt(0)).join(',')
    });

    // Show voice indicator
    $('#voiceIndicator').addClass('active').show();
    $('#voice_search').addClass('voice-active');

    // Set 25 second timeout ONLY for waiting for speech to start
    let startTimeout = setTimeout(() => {
        console.warn('⚠️ Speech failed to start within 25 seconds');
        synth.cancel();
        cleanupVoice();
    }, 25000);

    let speechStarted = false;

    // Event handlers
    utterance.onstart = () => {
        speechStarted = true;
        console.log('✅ Speech started - clearing timeout, will read complete response');
        clearTimeout(startTimeout); // Clear timeout once speech starts
        $('#voiceIndicator').addClass('speaking');
    };

    utterance.onend = () => {
        console.log('✅ Speech completed - full response read');
        if (!speechStarted) clearTimeout(startTimeout);
        cleanupVoice();
        // Reset voice conversation flag after speaking
        isVoiceConversation = false;
        console.log('🔇 Voice conversation ended - flag reset');
    };

    utterance.onerror = (event) => {
        console.error('❌ Speech error:', event.error, 'Target language:', targetLang, 'Voice:', voice?.name);
        
        // Enhanced error handling with Bengali-specific fallback
        if (event.error === 'language-not-supported' || event.error === 'voice-unavailable') {
            if (targetLang.startsWith('bn')) {
                console.warn('⚠️ Bengali voice failed, attempting fallback strategies...');
                
                // Try alternative Bengali voices
                const allVoices = synth.getVoices();
                const fallbackVoice = allVoices.find(v => 
                    v.lang.startsWith('bn') && v !== voice
                ) || allVoices.find(v => 
                    v.name.toLowerCase().includes('bengali') || v.name.toLowerCase().includes('bangla')
                ) || allVoices.find(v => v.lang.startsWith('en')); // English as last resort
                
                if (fallbackVoice && fallbackVoice !== voice) {
                    console.log('🔄 Retrying with fallback voice:', fallbackVoice.name, fallbackVoice.lang);
                    synth.cancel();
                    const fallbackUtterance = new SpeechSynthesisUtterance(cleanText);
                    fallbackUtterance.voice = fallbackVoice;
                    fallbackUtterance.lang = fallbackVoice.lang;
                    fallbackUtterance.rate = 0.8;
                    fallbackUtterance.pitch = 1.0;
                    fallbackUtterance.volume = 1.0;
                    
                    fallbackUtterance.onend = () => {
                        console.log('✅ Fallback speech completed');
                        cleanupVoice();
                        isVoiceConversation = false;
                        console.log('🔇 Voice conversation ended (fallback) - flag reset');
                    };
                    
                    fallbackUtterance.onerror = () => {
                        console.error('❌ Fallback speech also failed');
                        cleanupVoice();
                        isVoiceConversation = false;
                        console.log('🔇 Voice conversation ended (error) - flag reset');
                    };
                    
                    synth.speak(fallbackUtterance);
                    return; // Don't cleanup yet, let fallback try
                }
            } else {
                console.warn('⚠️ Language not supported:', targetLang, 'trying English fallback');
            }
        } else if (event.error === 'network') {
            console.warn('⚠️ Network error during speech synthesis');
        } else if (event.error === 'synthesis-failed') {
            console.warn('⚠️ Speech synthesis failed for language:', targetLang);
        }
        
        if (!speechStarted) clearTimeout(startTimeout);
        cleanupVoice();
        isVoiceConversation = false;
        console.log('🔇 Voice conversation ended (error) - flag reset');
    };

    // Start speaking
    console.log('🚀 Starting speech');
    synth.speak(utterance);

    function cleanupVoice() {
        $('#voiceIndicator').removeClass('active speaking').hide();
        $('#voice_search').removeClass('voice-active speaking');
        stopSilence(); // Stop the keep-alive
        console.log('🧹 Voice UI cleaned up');
    }
}
$(document).ready(function() {
    // Voice response controls
    $('#stopVoiceBtn').click(function() {
        console.log('🛑 Stop voice button clicked');
        stopSpeech();
        cleanupVoiceUI();
    });

    // Mic UI handlers
    $(document).click(function() {
        $('#microphone').removeClass('visible');
    });

    $('#voice_search').click(function(event) {
        stopSpeech();
        
        // Check browser support before showing microphone
        const SpeechRecognition = window.SpeechRecognition ||
                                 window.webkitSpeechRecognition ||
                                 window.mozSpeechRecognition;

        if (!SpeechRecognition) {
            showCustomAlert('Voice input is not supported in this browser. Please use Chrome, Edge, or Safari.');
            return;
        }

        // Check if running in secure context
        const isSecureContext = window.isSecureContext || 
                               window.location.protocol === 'https:' || 
                               window.location.hostname === 'localhost' || 
                               window.location.hostname === '127.0.0.1';

        if (!isSecureContext) {
            showCustomAlert('Voice recognition requires a secure connection (HTTPS). Please access this site via HTTPS or localhost to use voice input.');
            return;
        }

        // Show microphone modal with helpful status
        $('#microphone').addClass('visible');
        $('#mic-status').text('Click the microphone button below to start speaking').removeClass('active');
        $('#recoredText').text('');
        event.stopPropagation();
    });

    $('.recoder').click(function(event) {
        event.stopPropagation();
    });

    $('#microphone .close').click(function() {
        $('#microphone').removeClass('visible');
    });

    $('#customAlertClose').click(hideCustomAlert);
    $('#aboutLink').click(showAboutModal);
    $('#aboutModalClose').click(hideAboutModal);
});

// Populate voice/language selector with bn-BD priority and one voice per language
$(document).ready(function() {
    var synth = window.speechSynthesis;
    var $langSelect = $('#lang');

    function populateLanguages() {
        var voices = synth.getVoices();
        $langSelect.empty();

        // Build a map: language code -> best voice for that language
        function scoreVoice(v) {
            const name = (v.name || '').toLowerCase();
            const lang = (v.lang || '').toLowerCase();
            let score = 0;
            
            // Base provider scores (Google gets highest priority)
            if (name.includes('google')) score += 10;
            if (name.includes('microsoft')) score += 5;
            if (name.includes('natural') || name.includes('enhanced')) score += 3;
            if (PREFERRED_VOICE_ALIASES.map(n => n.toLowerCase()).includes(name)) score += 8;
            
            // Enhanced Bengali voice scoring
            if (lang.startsWith('bn') || name.includes('bengali') || name.includes('bangla')) {
                score += 20; // Very high priority for Bengali voices
                
                // Extra points for Google Bengali (best quality)
                if (name.includes('google')) score += 15;
                
                // Prefer Bangladesh variant over India
                if (lang === 'bn-bd' || name.includes('bangladesh')) score += 10;
                if (lang === 'bn-in') score += 7;
                
                // Prefer specific Bengali patterns
                if (name.includes('বাংলা')) score += 5; // Native script
                if (name.includes('microsoft') && lang.startsWith('bn')) score += 3;
            }
            
            return score;
        }

        const byLang = {};
        voices.forEach(v => {
            const lang = v.lang || '';
            if (!lang) return;
            if (!byLang[lang] || scoreVoice(v) > scoreVoice(byLang[lang])) {
                byLang[lang] = v;
            }
        });

        // Enhanced Bengali voice detection and mapping
        const bnVoices = voices.filter(v => {
            const lang = (v.lang || '').toLowerCase();
            const name = (v.name || '').toLowerCase();
            return lang.startsWith('bn') || 
                   name.includes('bengali') || 
                   name.includes('bangla') || 
                   name.includes('bangladesh') ||
                   name.includes('বাংলা');
        });
        
        console.log('🔍 Found Bengali voices:', bnVoices.map(v => `${v.name} (${v.lang})`));
        
        // Map the best Bengali voice to bn-BD if not already present
        if (!byLang['bn-BD'] && bnVoices.length > 0) {
            const bestBn = bnVoices.reduce((a, b) => (scoreVoice(a) >= scoreVoice(b) ? a : b));
            byLang['bn-BD'] = bestBn;
            console.log('✅ Mapped best Bengali voice to bn-BD:', bestBn.name, bestBn.lang);
        }
        
        // Also ensure other Bengali variants are properly mapped
        bnVoices.forEach(v => {
            const lang = v.lang || 'bn-BD';
            if (!byLang[lang] || scoreVoice(v) > scoreVoice(byLang[lang])) {
                byLang[lang] = v;
            }
        });

        // Sort languages: bn-BD first, then en-US, then others alphabetically
        const langs = Object.keys(byLang).sort((a,b) => {
            if (a === 'bn-BD') return -1;
            if (b === 'bn-BD') return 1;
            if (a === 'en-US') return -1;
            if (b === 'en-US') return 1;
            return a.localeCompare(b);
        });

        // If still no bn-BD in list, add a placeholder entry
        if (!langs.includes('bn-BD')) {
            langs.unshift('bn-BD');
        }

        // Populate options: one per language; value = lang, data-voice = voice name
        langs.forEach(lang => {
            const voice = byLang[lang];
            // Even without TTS voice, speech recognition can still work
            const label = voice ? `${lang} — ${voice.name}` : `${lang} — (Text input only)`;
            const $option = $('<option>', { value: lang, text: label });
            $option.attr('data-lang', lang);
            $option.attr('data-voice', voice ? voice.name : '');
            $langSelect.append($option);
        });

        // Restore preference or choose defaults with bn-BD priority
        const storedLang = localStorage.getItem(LANG_STORAGE_KEY) || '';
        const storedVoiceName = localStorage.getItem(VOICE_STORAGE_KEY) || '';

        let selected = false;
        function selectByLang(lang) {
            const opt = $langSelect.children().filter(function(){ return ($(this).val()||'') === lang; }).first();
            if (opt.length) { opt.prop('selected', true); return true; }
            return false;
        }

        // 1) Stored lang if present
        if (storedLang && selectByLang(storedLang)) selected = true;
        // 2) bn-BD
        if (!selected && selectByLang('bn-BD')) selected = true;
        // 3) en-US
        if (!selected && selectByLang('en-US')) selected = true;
        // 4) First available
        if (!selected && $langSelect.children().length > 0) $langSelect.children().first().prop('selected', true);

        // Persist the decided selection
        const selLang = ($langSelect.find('option:selected').attr('data-lang') || 'en-US');
        const selVoice = ($langSelect.find('option:selected').attr('data-voice') || '');
        if (selLang) localStorage.setItem(LANG_STORAGE_KEY, selLang);
        if (selVoice) localStorage.setItem(VOICE_STORAGE_KEY, selVoice);
    }

    populateLanguages();

    if (typeof synth.onvoiceschanged !== 'undefined') {
        synth.onvoiceschanged = () => {
            populateLanguages();
            validateBengaliVoices();
        };
    }
    
    // Validate Bengali voices on initial load
    validateBengaliVoices();
    
    // Function to validate and report Bengali voice availability
    function validateBengaliVoices() {
        const voices = synth.getVoices();
        const bengaliVoices = voices.filter(v => {
            const lang = (v.lang || '').toLowerCase();
            const name = (v.name || '').toLowerCase();
            return lang.startsWith('bn') || 
                   name.includes('bengali') || 
                   name.includes('bangla') || 
                   name.includes('bangladesh');
        });
        
        console.log('🔍 Bengali Voice Validation Report:');
        console.log(`   Total voices available: ${voices.length}`);
        console.log(`   Bengali voices found: ${bengaliVoices.length}`);
        
        if (bengaliVoices.length > 0) {
            console.log('✅ Available Bengali voices:');
            bengaliVoices.forEach((v, i) => {
                console.log(`   ${i + 1}. ${v.name} (${v.lang}) - Local: ${v.localService}`);
            });
        } else {
            console.warn('⚠️ No Bengali voices detected on this device');
            console.log('💡 Recommendation: Install Bengali language pack for better TTS support');
        }
    }

    // Save user selection changes
    $langSelect.on('change', function() {
        var lang = ($(this).find('option:selected').attr('data-lang') || '');
        var voice = ($(this).find('option:selected').attr('data-voice') || '');
        if (voice) localStorage.setItem(VOICE_STORAGE_KEY, voice);
        if (lang) localStorage.setItem(LANG_STORAGE_KEY, lang);
    });
});

$(document).ready(function() {
    var speech;
    let r = 0;
    let voices = [];
    let synth;
    let micActive = false;
    let mediaStream = null;

    window.addEventListener("load", () => {
        synth = window.speechSynthesis;
        voices = synth.getVoices();
        if (synth.onvoiceschanged !== undefined) {
            synth.onvoiceschanged = voices;
        }
    });

    function checkhSpeach() {
        $('#speakBtn').removeClass('active');
        
        // Mark this as a voice-initiated conversation with extended timeout for long responses
        isVoiceConversation = true;
        startSilence(); // Start keep-alive for mobile browsers
        console.log('🎤 Voice conversation initiated');
        
        // Set a generous timeout for voice conversation (5 minutes) to handle long backend processing
        if (window.voiceConversationTimeout) {
            clearTimeout(window.voiceConversationTimeout);
        }
        window.voiceConversationTimeout = setTimeout(() => {
            console.log('⏰ Voice conversation timeout - resetting state');
            isVoiceConversation = false;
        }, 300000); // 5 minutes timeout
        
        if (r == 0) {
            // Play end audio if available
            try {
                new Audio('assets/audio/end.mp3').play().catch(e => console.log('End audio play failed:', e));
            } catch (e) {
                console.log('End audio not available:', e);
            }
            
            setTimeout(function() {
                if (speech && speech.recognition) {
                    speech.recognition.stop();
                    speech.listening = false;
                }
                speechText();
                
                // Process the voice input
                let text = $('#recoredText').text().trim();
                if (text && text.length > 1 && text !== 'Listening...') {
                    console.log('🎤 Processing voice input:', text);
                    $('#prompt').val(text);
                    $('#recoredText').text('');
                    $("#prompt").trigger("keyup");
                    
                    // Hide microphone UI and send the message
                    setTimeout(() => {
                        $('#microphone').removeClass('visible');
                        $('#sendBtn').click();
                    }, 100);
                } else {
                    console.warn('⚠️ No valid voice input detected');
                    $('#microphone').removeClass('visible');
                }
            }, 2000); // Reduced to 2 seconds for better mobile experience
        }
        r++;
        setTimeout(function() {
            r = 0;
        }, 3000); // Reduced to 3 seconds for mobile compatibility
    }

    $('#speakBtn').click(function() {
        // Prevent multiple rapid clicks (debouncing for mobile)
        if ($(this).hasClass('processing')) {
            return;
        }
        
        $(this).addClass('processing');
        setTimeout(() => $(this).removeClass('processing'), 1000);

        if ($('#speakBtn').hasClass('active')) {
            // Stop listening when user clicks again
            console.log('🛑 Stopping voice recognition');
            $('#speakBtn').removeClass('active');
            if (speech && speech.recognition) {
                speech.recognition.stop();
                speech.listening = false;
            }
            $('#mic-status').removeClass('active').text('Microphone is off');
        } else {
            // Start listening
            console.log('🎤 Starting voice recognition');
            $('#speakBtn').addClass('active');
            
            // Play audio feedback if available
            try {
                new Audio('assets/audio/start.mp3').play().catch(e => console.log('Audio play failed:', e));
            } catch (e) {
                console.log('Audio not available:', e);
            }
            
            // Reset speech state
            if (speech) {
                speech.finalTranscript = '';
                speech.interimTranscript = '';
                speech.text = '';
            }
            
            setTimeout(function() {
                try {
                    // Recognition language = selected language (prioritize stored), default bn-BD then en-US
                    let language = ($('#microphone select option:selected').data('lang') || '').toString();
                    if (!language) language = (localStorage.getItem(LANG_STORAGE_KEY) || '');
                    if (!language) language = 'bn-BD';
                    if (!language) language = 'en-US';
                    
                    console.log('🌍 Voice recognition requested language:', language);
                    
                    // Safari detection for automatic fallback
                    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent) && 
                                    !/CriOS|FxiOS|EdgiOS/i.test(navigator.userAgent);
                    
                    // If Safari and Bengali selected, silently use English for recognition
                    if (isSafari && language.toLowerCase().startsWith('bn')) {
                        console.log('🍎 Safari + Bengali detected - using English for voice recognition');
                        language = 'en-US';
                        // Don't show alert here, just use English silently
                    }
                    
                    speak(language);
                    if (speech && speech.recognition) {
                        speech.recognition.start();
                    }
                } catch (error) {
                    console.error('🎤 Error starting voice recognition:', error);
                    $('#speakBtn').removeClass('active');
                    showCustomAlert('Voice recognition failed to start. Please try again.');
                }
            }, 300); // Slight delay to allow UI update

            $('#mic-status').addClass('active').text('Microphone is on - Speak now');
        }
    });

    function speak(language) {
        window.SpeechRecognition = window.SpeechRecognition ||
            window.webkitSpeechRecognition ||
            window.mozSpeechRecognization;

        if (!window.SpeechRecognition) {
            console.error('🎤 Speech recognition not supported in this browser');
            showCustomAlert('Voice input is not supported in this browser. Please use a supported browser like Chrome, Edge, or Safari.');
            return;
        }

        // Check if running in secure context (HTTPS or localhost)
        const isSecureContext = window.isSecureContext || 
                               window.location.protocol === 'https:' || 
                               window.location.hostname === 'localhost' || 
                               window.location.hostname === '127.0.0.1';

        if (!isSecureContext) {
            console.error('🔒 Speech recognition requires HTTPS or localhost');
            showCustomAlert('Voice recognition requires a secure connection (HTTPS). Please access this site via HTTPS or localhost.');
            $('#microphone').removeClass('visible');
            $('#speakBtn').removeClass('active');
            return;
        }

        // Safari detection and language support check  
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent) && 
                        !/CriOS|FxiOS|EdgiOS/i.test(navigator.userAgent);
        let recognitionLang = language;
        
        if (isSafari) {
            console.log('🍎 Safari browser detected - checking language support');
            
            // Safari has limited language support for speech recognition (input)
            const safariSupportedLanguages = [
                'en-US', 'en-GB', 'en-AU', 'en-CA', 'en-IN', 'en-NZ', 'en-ZA',
                'de-DE', 'es-ES', 'es-MX', 'fr-FR', 'fr-CA', 'it-IT', 
                'ja-JP', 'ko-KR', 'pt-BR', 'pt-PT', 'zh-CN', 'zh-TW', 'zh-HK',
                'nl-NL', 'ru-RU', 'ar-SA', 'da-DK', 'fi-FI', 'he-IL',
                'id-ID', 'ms-MY', 'no-NO', 'pl-PL', 'ro-RO', 'sv-SE',
                'th-TH', 'tr-TR', 'cs-CZ', 'el-GR', 'hu-HU', 'vi-VN'
            ];
            
            const langCode = language.toLowerCase();
            const isSupported = safariSupportedLanguages.some(sl => langCode === sl.toLowerCase());
            
            if (!isSupported) {
                console.log(`ℹ️ Safari using English fallback for ${language} voice input`);
                recognitionLang = 'en-US';
                // Don't show alert - just silently use English for better UX
            } else {
                console.log(`✅ Safari supports ${language} for speech recognition`);
            }
        }

        speech = {
            enabled: true,
            listening: false,
            recognition: new SpeechRecognition(),
            text: "",
            finalTranscript: "",
            interimTranscript: "",
            requestedLang: language,
            actualLang: recognitionLang
        };
        
        // Enhanced settings for mobile Chrome compatibility
        speech.recognition.continuous = false; // Changed to false for better mobile experience
        speech.recognition.interimResults = true;
        speech.recognition.lang = recognitionLang; // Use Safari-compatible language
        speech.recognition.maxAlternatives = 1;
        
        console.log('🎙️ Speech recognition configured:', {
            requestedLang: language,
            actualLang: recognitionLang,
            isSafari: isSafari || false
        });
        
        // Mobile Chrome optimization
        if (/Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)) {
            speech.recognition.continuous = false; // Disable continuous for mobile
            console.log('📱 Mobile device detected - optimizing voice recognition');
        }

        // Enhanced result handling with better final result detection
        speech.recognition.addEventListener("result", (event) => {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript;
                } else {
                    interimTranscript += transcript;
                }
            }

            // Update display with interim results
            const displayText = finalTranscript + interimTranscript;
            $('#recoredText').text(displayText);
            
            // Store the current state
            speech.finalTranscript = finalTranscript;
            speech.interimTranscript = interimTranscript;
            speech.text = displayText;

            // Process final results
            if (finalTranscript) {
                console.log('🎤 Final transcript received:', finalTranscript);
                setTimeout(() => {
                    checkhSpeach();
                }, 500); // Small delay to ensure processing
            }
        });

        // Enhanced error handling for mobile Chrome
        speech.recognition.addEventListener("error", (event) => {
            console.error('🎤 Speech recognition error:', event.error);
            
            switch(event.error) {
                case 'no-speech':
                    console.warn('⚠️ No speech detected. Please try again.');
                    showCustomAlert('No speech detected. Please speak clearly and try again.');
                    break;
                case 'audio-capture':
                    showCustomAlert('Microphone not found or not working. Please check your microphone and try again.');
                    break;
                case 'not-allowed':
                    showCustomAlert('Microphone access denied. Please allow microphone access in your browser settings and try again.');
                    break;
                case 'network':
                    showCustomAlert('Network error occurred. Please check your internet connection and try again.');
                    break;
                case 'language-not-supported':
                    console.error('❌ Language not supported:', speech?.requestedLang);
                    // Try to restart with English fallback
                    console.log('🔄 Attempting restart with English fallback');
                    if (speech && speech.recognition) {
                        try {
                            speech.recognition.lang = 'en-US';
                            setTimeout(() => {
                                if (speech && speech.recognition && speech.listening) {
                                    speech.recognition.start();
                                    console.log('✅ Restarted with English');
                                }
                            }, 100);
                        } catch (e) {
                            console.error('Failed to restart with English:', e);
                        }
                    }
                    break;
                case 'service-not-allowed':
                    // This typically means HTTPS is required or browser doesn't allow speech API
                    const isSecureContext = window.isSecureContext || 
                                          window.location.protocol === 'https:' || 
                                          window.location.hostname === 'localhost' || 
                                          window.location.hostname === '127.0.0.1';
                    
                    if (!isSecureContext) {
                        showCustomAlert('Voice recognition requires a secure connection (HTTPS). Please access this site via HTTPS.');
                    } else {
                        showCustomAlert('Speech recognition service is not available. Please ensure microphone permissions are granted and try again.');
                    }
                    break;
                case 'aborted':
                    console.log('🎤 Speech recognition aborted');
                    break;
                default:
                    console.warn('⚠️ Speech recognition error:', event.error);
                    showCustomAlert('Voice recognition encountered an error. Please try again.');
            }
            
            // Reset UI on error
            $('#voice_search').removeClass('voice-active');
            $('#speakBtn').removeClass('active');
            $('#recoredText').text('');
        });

        // Handle recognition end
        speech.recognition.addEventListener("end", () => {
            console.log('🎤 Speech recognition ended');
            $('#voice_search').removeClass('voice-active');
            
            // For mobile, auto-restart if we're still listening and no final result
            if (speech.listening && !speech.finalTranscript && /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)) {
                console.log('📱 Auto-restarting recognition for mobile');
                setTimeout(() => {
                    if (speech.listening) {
                        try {
                            speech.recognition.start();
                        } catch (e) {
                            console.error('Failed to restart recognition:', e);
                        }
                    }
                }, 100);
            }
        });

        // Handle recognition start
        speech.recognition.addEventListener("start", () => {
            console.log('🎤 Speech recognition started');
            $('#voice_search').addClass('voice-active');
            speech.listening = true;
        });
    }



    function speechText() {
        let say = $('#recoredText').text().trim();
        if (!say) return;

        isVoice = 1;

        let synth = window.speechSynthesis;

        let voices = synth.getVoices();
        if (!voices.length) {
            synth.onvoiceschanged = () => speechText(); // Retry after voices load
            return;
        }

        const selectedOption = $('#lang option:selected');
        const selectedLang = selectedOption.data('lang') || 'bn-BD';
        const selectedVoiceName = selectedOption.data('voice') || localStorage.getItem(VOICE_STORAGE_KEY) || '';

        // Prefer the exact selected voice by name, then fallback by language
        let voiceToUse = voices.find(v => v.name === selectedVoiceName) ||
            voices.find(v => (v.lang || '') === selectedLang) ||
            voices.find(v => (v.lang || '').startsWith(selectedLang.split('-')[0])) ||
            voices.find(v => (v.lang || '').startsWith('bn-BD')) ||
            voices.find(v => (v.lang || '').startsWith('en-US'));

        if (selectedLang.startsWith('en')) say = 'Showing prompt: ' + say;
        else if (selectedLang.startsWith('bn')) say = 'প্রম্পট দেখানো হচ্ছে: ' + say;
        else if (selectedLang.startsWith('hi')) say = 'प्रॉम्प्ट दिखा रहा है: ' + say;
        else if (selectedLang.startsWith('ur')) say = 'پرومپٹ دکھا رہا ہے: ' + say;
        else if (selectedLang.startsWith('fr')) say = 'affichage de l’invite : ' + say;
        else if (selectedLang.startsWith('pt')) say = 'mostrando o prompt: ' + say;

        // Speak
        let utterance = new SpeechSynthesisUtterance(say);
        utterance.lang = selectedLang || 'bn-BD';
        if (voiceToUse) utterance.voice = voiceToUse;
        synth.speak(utterance);
    }

    $('#stopVoiceBtn').click(function() {
        speech.recognition.stop();
        stopSpeech();
        isVoiceConversation = false; // Reset voice conversation flag
        stopSilence(); // Ensure keep-alive is stopped
    });

});

function showCustomAlert(message) {
    $('#customAlertMessage').html(message);
    $('#customAlert').addClass('visible');
}

function hideCustomAlert() {
    $('#customAlert').removeClass('visible');
}

function showAboutModal() {
    $('#aboutModal').addClass('visible');
    
    // Prevent body scrolling when modal is open (mobile fix)
    $('body').addClass('modal-open');
    
    // Add escape key handler
    $(document).on('keydown.aboutModal', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            hideAboutModal();
        }
    });
    
    // Add click outside to close (mobile-friendly)
    $('#aboutModal').on('click.aboutModal', function(e) {
        if (e.target === this) {
            hideAboutModal();
        }
    });
    
    // Focus management for accessibility
    setTimeout(() => {
        $('#aboutModalClose').focus();
    }, 100);
}

function hideAboutModal() {
    $('#aboutModal').removeClass('visible');
    
    // Re-enable body scrolling
    $('body').removeClass('modal-open');
    
    // Remove event handlers
    $(document).off('keydown.aboutModal');
    $('#aboutModal').off('click.aboutModal');
    
    // Return focus to the about link
    $('#aboutLink').focus();
}