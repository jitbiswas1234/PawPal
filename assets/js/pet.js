/**
 * PawPal - Virtual Pet Simulator Gameplay JS Controller
 */

$(document).ready(function() {
    // Global Configurations (available on all pages via meta tags in header)
    const baseUrl = $('meta[name="base-url"]').attr('content') || '/pet';
    let csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    // ============================================
    // Sidebar Mobile Toggle Interactions
    // ============================================
    $(document).on('click', '#toggleSidebarBtn', function(e) {
        e.preventDefault();
        $('#userSidebar').addClass('active');
        // Add backdrop if it doesn't exist
        if (!$('.sidebar-backdrop').length) {
            $('<div class="sidebar-backdrop"></div>').appendTo('body');
        }
    });

    $(document).on('click', '#closeSidebarBtn, .sidebar-backdrop', function(e) {
        e.preventDefault();
        $('#userSidebar').removeClass('active');
        $('.sidebar-backdrop').remove();
    });

    // ============================================
    // Navbar Notification Interactions (Global)
    // ============================================
    // Individual click mark read
    $(document).on('click', '.notif-item.unread', function(e) {
        const item = $(this);
        const notifId = item.data('id');
        if (!notifId) return;

        $.ajax({
            url: `${baseUrl}/ajax/mark_notification_read.php`,
            type: 'POST',
            dataType: 'json',
            data: {
                id: notifId,
                csrf_token: csrfToken
            },
            success: function(res) {
                if (res.success) {
                    item.removeClass('unread');
                    item.find('p').removeClass('fw-semibold');
                    updateNotifBadgeCount(res.unread_count);
                }
            }
        });
    });

    // Mark all read click
    $(document).on('click', '#markAllReadBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            url: `${baseUrl}/ajax/mark_notification_read.php`,
            type: 'POST',
            dataType: 'json',
            data: {
                id: 'all',
                csrf_token: csrfToken
            },
            success: function(res) {
                if (res.success) {
                    $('.notif-item').removeClass('unread');
                    $('.notif-item').find('p').removeClass('fw-semibold');
                    updateNotifBadgeCount(0);
                    $('#markAllReadBtn').remove();
                }
            }
        });
    });

    function updateNotifBadgeCount(count) {
        const badge = $('.notif-badge');
        if (count > 0) {
            if (badge.length) {
                badge.text(count);
            } else {
                $('#notifDropdown').append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow notif-badge">${count}</span>`);
            }
        } else {
            badge.remove();
        }
    }

    // ============================================
    // Gameplay Page Specific Initialization
    // ============================================
    const gameContainer = $('#gameplay-container');
    if (!gameContainer.length) return; // Exit early if not on the gameplay page

    const petId = gameContainer.data('pet-id');
    if (gameContainer.data('csrf')) {
        csrfToken = gameContainer.data('csrf');
    }

    // ============================================
    // 1. Audio Synthesizer (Web Audio API)
    // ============================================
    function playSynthesizedSound(type) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'feed') {
                // Happy munching pop-pops
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(200, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(450, ctx.currentTime + 0.15);
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            } else if (type === 'water') {
                // Bubbling pitches
                osc.type = 'sine';
                osc.frequency.setValueAtTime(300, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(800, ctx.currentTime + 0.25);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } else if (type === 'play') {
                // Fun bouncy sweeps
                osc.type = 'sine';
                osc.frequency.setValueAtTime(350, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(900, ctx.currentTime + 0.3);
                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.35);
                osc.start();
                osc.stop(ctx.currentTime + 0.35);
            } else if (type === 'groom') {
                // Synthesized spray / brush sound (short bandpassed white noise)
                const bufferSize = ctx.sampleRate * 0.25;
                const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                const data = buffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) {
                    data[i] = Math.random() * 2 - 1;
                }
                const noise = ctx.createBufferSource();
                noise.buffer = buffer;
                
                const filter = ctx.createBiquadFilter();
                filter.type = 'bandpass';
                filter.frequency.value = 1200;
                
                noise.connect(filter);
                filter.connect(gain);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.001, ctx.currentTime + 0.25);
                
                noise.start();
                noise.stop(ctx.currentTime + 0.25);
            } else if (type === 'sleep') {
                // Warm, soothing low-pitched hum
                osc.type = 'sine';
                osc.frequency.setValueAtTime(150, ctx.currentTime);
                osc.frequency.linearRampToValueAtTime(100, ctx.currentTime + 1.0);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.001, ctx.currentTime + 1.2);
                osc.start();
                osc.stop(ctx.currentTime + 1.2);
            } else if (type === 'medicine') {
                // Clinical ringing chime
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(520, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(1040, ctx.currentTime + 0.35);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                osc.start();
                osc.stop(ctx.currentTime + 0.4);
            } else if (type === 'train') {
                // Gym training sweep
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, ctx.currentTime);
                osc.frequency.linearRampToValueAtTime(440, ctx.currentTime + 0.4);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.001, ctx.currentTime + 0.45);
                osc.start();
                osc.stop(ctx.currentTime + 0.45);
            } else if (type === 'levelup') {
                // Uplifting arpeggio: C4 -> E4 -> G4 -> C5
                const notes = [261.63, 329.63, 392.00, 523.25];
                notes.forEach((freq, idx) => {
                    const o = ctx.createOscillator();
                    const g = ctx.createGain();
                    o.type = 'sine';
                    o.frequency.value = freq;
                    o.connect(g);
                    g.connect(ctx.destination);
                    const start = ctx.currentTime + idx * 0.12;
                    g.gain.setValueAtTime(0.25, start);
                    g.gain.linearRampToValueAtTime(0.001, start + 0.35);
                    o.start(start);
                    o.stop(start + 0.35);
                });
            }
        } catch (e) {
            console.log("AudioContext playback blocked/unsupported.", e);
        }
    }

    function playSound(action) {
        const audio = document.getElementById('sfx' + action.charAt(0).toUpperCase() + action.slice(1));
        if (audio && audio.play) {
            audio.currentTime = 0;
            audio.play().catch(() => playSynthesizedSound(action));
        } else {
            playSynthesizedSound(action);
        }
    }

    // ============================================
    // 2. Visual Effects & Animations
    // ============================================
    function showReaction(text, type = 'success') {
        const stage = $('#petStage');
        if (!stage.length) return;

        const colors = {
            'success': '#22c55e',
            'warning': '#f59e0b',
            'danger': '#ef4444',
            'info': '#3b82f6'
        };

        const reaction = $('<div class="sparkle-effect fw-bold text-nowrap"></div>');
        reaction.text(text);
        reaction.css({
            'color': colors[type] || colors['success'],
            'left': (Math.random() * 40 + 30) + '%',
            'top': (Math.random() * 20 + 35) + '%',
            'position': 'absolute',
            'transform': 'translate(-50%, -50%)',
            'text-shadow': '0 2px 8px rgba(0,0,0,0.25)'
        });

        stage.append(reaction);
        setTimeout(() => reaction.remove(), 800);
    }

    function animateAvatar(animationClass) {
        const avatar = $('#petAvatar');
        if (!avatar.length) return;
        
        avatar.addClass(animationClass);
        setTimeout(() => avatar.removeClass(animationClass), 600);
    }

    function triggerConfetti() {
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    }

    // ============================================
    // 2b. Speech Bubbles & Gameplay Animation Overlays
    // ============================================
    const stage = $('#petStage');
    if (stage.length && !$('#petSpeechBubble').length) {
        stage.append('<div class="pet-speech-bubble" id="petSpeechBubble"></div>');
    }

    const cutePhrases = {
        feed: ["Yummy! 🍖", "Nom nom nom! 😋", "So delicious!", "More treats please! 💕", "My tummy is happy!"],
        water: ["Glug glug! 💧", "So refreshing!", "Thirst quenched! 🥤", "Ahhh, cold water!", "Thank you, human!"],
        play: ["Wheee! ⚽", "Catch the ball!", "This is fun! 🎉", "Let's run! 🐾", "Best day ever!"],
        groom: ["Squeaky clean! 🫧", "Sparkle sparkle! ✨", "So fresh!", "I smell amazing! 🌸", "Feels so good!"],
        sleep: ["Zzz... 😴", "Snore... 💤", "Sweet dreams!", "Sleeping tight...", "Good night!"],
        medicine: ["Phew, feel better! ❤️", "Bitter, but helpful!", "Healing powers!", "Energy loading..."],
        train: ["I'm learning! 🎓", "Focus! 💪", "Obedient pet!", "Look at my skills!", "Aced it! ⭐"]
    };

    let speechBubbleTimeout;
    function showSpeechBubble(action) {
        const bubble = $('#petSpeechBubble');
        if (!bubble.length) return;

        const phrases = cutePhrases[action];
        if (!phrases) return;
        const randomPhrase = phrases[Math.floor(Math.random() * phrases.length)];

        clearTimeout(speechBubbleTimeout);
        bubble.text(randomPhrase);
        bubble.addClass('active');

        speechBubbleTimeout = setTimeout(() => {
            bubble.removeClass('active');
        }, 2200);
    }

    function playGameplayAnimation(action) {
        const stage = $('#petStage');
        if (!stage.length) return;

        if (action === 'feed') {
            const bowl = $('<div class="game-overlay-element food-bowl-anim"></div>');
            stage.append(bowl);
            setTimeout(() => bowl.remove(), 1500);
        } else if (action === 'water') {
            const glass = $('<div class="game-overlay-element water-glass-anim"></div>');
            stage.append(glass);
            setTimeout(() => glass.remove(), 1500);
        } else if (action === 'play') {
            const ball = $('<div class="game-overlay-element toy-ball-anim"></div>');
            stage.append(ball);
            setTimeout(() => ball.remove(), 1600);
        } else if (action === 'groom') {
            const brush = $('<div class="game-overlay-element groom-brush-anim"></div>');
            stage.append(brush);
            setTimeout(() => brush.remove(), 1600);

            // Spawn soap bubbles
            for (let i = 0; i < 6; i++) {
                setTimeout(() => {
                    const bubble = $('<div class="game-overlay-element soap-bubble"></div>');
                    bubble.css({
                        'left': (Math.random() * 50 + 25) + '%',
                        'animation-delay': (Math.random() * 0.2) + 's'
                    });
                    stage.append(bubble);
                    setTimeout(() => bubble.remove(), 1800);
                }, i * 180);
            }
        } else if (action === 'sleep') {
            // Spawn floating Zzz
            for (let i = 0; i < 4; i++) {
                setTimeout(() => {
                    const zzz = $('<div class="game-overlay-element sleeping-zzz">Zzz</div>');
                    zzz.css({
                        'left': (Math.random() * 30 + 35) + '%',
                        'font-size': (1 + i * 0.25) + 'rem'
                    });
                    stage.append(zzz);
                    setTimeout(() => zzz.remove(), 2200);
                }, i * 450);
            }
        } else if (action === 'medicine' || action === 'train') {
            // Star/Heart bursts
            for (let i = 0; i < 10; i++) {
                const particle = $('<div class="game-overlay-element sparkle-particle"></div>');
                const angle = (i / 10) * 2 * Math.PI;
                const dist = Math.random() * 80 + 50;
                const dx = (Math.cos(angle) * dist) + 'px';
                const dy = (Math.sin(angle) * dist - 80) + 'px';
                const rot = (Math.random() * 360) + 'deg';
                
                particle.css({
                    '--dx': dx,
                    '--dy': dy,
                    '--rot': rot,
                    'left': '50%',
                    'background': action === 'medicine' ? '#ef4444' : '#fbbf24',
                    'clip-path': action === 'medicine' ? 'polygon(50% 15%, 65% 0%, 80% 0%, 100% 20%, 100% 45%, 50% 95%, 0% 45%, 0% 20%, 20% 0%, 35% 15%)' : 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)'
                });
                stage.append(particle);
                setTimeout(() => particle.remove(), 1200);
            }
        }
    }

    function playInteractiveEffects(action) {
        const avatar = $('#petAvatar');

        // 1. Map actions to active state classes on the CSS pet container
        let stateClass = '';
        if (action === 'feed') stateClass = 'eating';
        else if (action === 'water') stateClass = 'drinking';
        else if (action === 'play' || action === 'train') stateClass = 'playing';
        else if (action === 'sleep') stateClass = 'sleeping';

        if (avatar.length) {
            // Wake up if sleeping and doing other actions
            if (action !== 'sleep') {
                avatar.removeClass('sleeping');
            }

            if (stateClass) {
                avatar.removeClass('eating drinking playing'); // Clear transient states
                avatar.addClass(stateClass);

                // Auto-clear eating, drinking, playing after 2 seconds
                if (action !== 'sleep') {
                    setTimeout(() => {
                        avatar.removeClass(stateClass);
                    }, 2000);
                }
            }
        }

        // 2. Animate avatar container itself
        if (action === 'feed' || action === 'water' || action === 'play' || action === 'train') {
            animateAvatar('bounce-animation');
        } else if (action === 'groom') {
            animateAvatar('spin-animation');
        } else if (action === 'medicine') {
            animateAvatar('shake-animation');
        }

        // 3. Play game overlays
        playGameplayAnimation(action);

        // 4. Show bubble text
        showSpeechBubble(action);
    }

    // ============================================
    // 3. Stat Bar Smooth Updates
    // ============================================
    function updateStatBar(name, value, inverse = false) {
        const bar = $('.pet-' + name + '-bar');
        const text = $('.pet-' + name + '-text');
        
        if (bar.length) {
            bar.css('width', value + '%');
            
            // Get proper color mapping
            let color = '#22c55e'; // default success
            let checkValue = inverse ? (100 - value) : value;
            if (checkValue < 40) {
                color = '#ef4444'; // danger
            } else if (checkValue < 70) {
                color = '#f59e0b'; // warning
            }
            
            bar.css('background-color', color);
        }
        
        if (text.length) {
            text.text(value + '/100');
        }
    }

    function updateLevelAndXP(level, xp, xpNeeded) {
        $('.pet-level-display').text(level);
        $('.pet-xp-display').text(xp);
        
        // Calculate progress percentage
        // Need to query thresholds dynamically or do a standard linear approximation
        // In our setup, thresholds are standard: 1->50, 2->150, 3->250 etc.
        // Let's approximate based on range
        let prevThreshold = 0;
        const thresholds = [0, 0, 50, 150, 250, 400, 600, 800, 1100, 1500, 2000];
        if (level < thresholds.length) {
            prevThreshold = thresholds[level];
        } else {
            prevThreshold = 2000;
        }

        const xpRange = xpNeeded - prevThreshold;
        const xpProgress = xp - prevThreshold;
        let percent = 0;
        if (xpRange > 0) {
            percent = Math.max(0, minMaxClamp((xpProgress / xpRange) * 100));
        } else {
            percent = 100;
        }

        const progressBar = $('.pet-xp-progress-bar');
        if (progressBar.length) {
            progressBar.css('width', percent + '%');
            progressBar.attr('aria-valuenow', percent);
        }
    }

    function minMaxClamp(val) {
        return Math.max(0, Math.min(100, val));
    }

    function updateMoodDisplay(mood, emoji, color) {
        const moodBadge = $('.pet-mood-display');
        if (moodBadge.length) {
            moodBadge.css({
                'background-color': color + '22',
                'color': color
            });
            moodBadge.find('.mood-emoji').text(emoji);
            moodBadge.find('.mood-text').text(mood);
        }

        // Also change stage background theme
        const stage = $('#petStage');
        if (stage.length) {
            stage.removeClass('happy sick hungry sleepy sad dirty ecstatic');
            stage.addClass(mood.toLowerCase());
        }
    }

    function updateCoinDisplays(coins) {
        $('.user-coins-display').text(Number(coins).toLocaleString());
    }

    // ============================================
    // 4. Cooldown & Global Action Lock System
    // ============================================
    let isGlobalLocked = false;

    function disableAllActions() {
        isGlobalLocked = true;
        $('.care-btn, .use-item-btn').prop('disabled', true);
    }

    function enableAllActions() {
        isGlobalLocked = false;
        $('.care-btn').each(function() {
            const btn = $(this);
            if (!btn.hasClass('cooldown') && btn.data('action')) {
                btn.prop('disabled', false);
            }
        });
        $('.use-item-btn').each(function() {
            const btn = $(this);
            const qtySpan = $(`.item-qty-${btn.data('item-id')}`);
            const qty = qtySpan.length ? parseInt(qtySpan.text()) : 0;
            if (qty > 0) {
                btn.prop('disabled', false);
            }
        });
    }
    const cooldownDurations = {
        'feed': 8,
        'water': 6,
        'play': 12,
        'groom': 15,
        'sleep': 25,
        'medicine': 15,
        'train': 20
    };

    function startCooldown(button, action) {
        const duration = cooldownDurations[action] || 10;
        button.addClass('cooldown');
        button.prop('disabled', true);

        const overlay = $('<div class="cooldown-overlay"></div>');
        button.append(overlay);
        
        let remaining = duration;
        overlay.text(remaining + 's');

        const interval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(interval);
                overlay.remove();
                button.removeClass('cooldown');
                if (!isGlobalLocked) {
                    button.prop('disabled', false);
                }
            } else {
                overlay.text(remaining + 's');
            }
        }, 1000);
    }

    // ============================================
    // 5. AJAX Care Actions (Free / Cooldown based)
    // ============================================
    $('.care-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const action = btn.data('action');
        if (!action) return; // logs/history link doesn't have action
        if (isGlobalLocked) return;

        disableAllActions();

        $.ajax({
            url: `${baseUrl}/ajax/${action}_pet.php`,
            type: 'POST',
            dataType: 'json',
            data: {
                pet_id: petId,
                csrf_token: csrfToken
            },
            success: function(res) {
                if (res.success) {
                    // Play sound & animate
                    playSound(action);
                    
                    // Trigger interactive gameplay effects
                    playInteractiveEffects(action);
                    if (res.coins_earned > 0) {
                        setTimeout(() => showReaction(`+${res.coins_earned} Coins 🪙`, 'info'), 250);
                    }
                    if (res.xp_earned > 0) {
                        setTimeout(() => showReaction(`+${res.xp_earned} XP ⭐`, 'info'), 450);
                    }

                    // Update UI stats
                    const stats = res.stats;
                    updateStatBar('health', stats.health);
                    updateStatBar('hunger', stats.hunger, true);
                    updateStatBar('happiness', stats.happiness);
                    updateStatBar('energy', stats.energy);
                    updateStatBar('cleanliness', stats.cleanliness);

                    // Update XP, Level, Mood and Coins
                    updateLevelAndXP(res.level, res.xp, res.xp_needed);
                    updateMoodDisplay(stats.mood, stats.emoji, stats.mood_color);
                    updateCoinDisplays(res.user_coins);

                    // Level Up celebration
                    if (res.leveled_up) {
                        setTimeout(() => {
                            playSound('levelup');
                            triggerConfetti();
                            $('.pet-name-display-modal').text(gameContainer.find('.pet-name-display').text());
                            $('.new-level-display-modal').text(res.level);
                            $('#levelUpModal').modal('show');
                        }, 800);
                    }

                    // Cooldown overlay
                    startCooldown(btn, action);

                    // Unlock controls after the 2-second animation concludes
                    setTimeout(() => {
                        enableAllActions();
                    }, 2000);
                } else {
                    showReaction(res.message, 'danger');
                    enableAllActions();
                }
            },
            error: function() {
                showReaction('Action failed. Try again!', 'danger');
                enableAllActions();
            }
        });
    });

    // ============================================
    // 6. Inventory Item Usage
    // ============================================
    $('.use-item-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const itemId = btn.data('item-id');
        const action = btn.data('action'); // feed, play, medicine

        if (!itemId || !action) return;
        if (isGlobalLocked) return;

        disableAllActions();

        $.ajax({
            url: `${baseUrl}/ajax/${action}_pet.php`,
            type: 'POST',
            dataType: 'json',
            data: {
                pet_id: petId,
                item_id: itemId,
                csrf_token: csrfToken
            },
            success: function(res) {
                if (res.success) {
                    playSound(action);

                    // Trigger interactive gameplay effects
                    playInteractiveEffects(action);
                    if (res.xp_earned > 0) {
                        setTimeout(() => showReaction(`+${res.xp_earned} XP ⭐`, 'info'), 250);
                    }

                    // Update UI stats
                    const stats = res.stats;
                    updateStatBar('health', stats.health);
                    updateStatBar('hunger', stats.hunger, true);
                    updateStatBar('happiness', stats.happiness);
                    updateStatBar('energy', stats.energy);
                    updateStatBar('cleanliness', stats.cleanliness);

                    // Update XP, Level, Mood and Coins
                    updateLevelAndXP(res.level, res.xp, res.xp_needed);
                    updateMoodDisplay(stats.mood, stats.emoji, stats.mood_color);
                    updateCoinDisplays(res.user_coins);

                    // Update remaining item quantity on UI
                    const qtySpan = $(`.item-qty-${res.item_id}`);
                    if (qtySpan.length) {
                        qtySpan.text(res.item_qty);
                        if (res.item_qty <= 0) {
                            // If out of quantity, disable or hide item card
                            btn.closest('.card').css('opacity', '0.5');
                            btn.text('Spent').prop('disabled', true).removeClass('btn-outline-primary').addClass('btn-light');
                        } else {
                            btn.prop('disabled', false);
                        }
                    }

                    // Level Up celebration
                    if (res.leveled_up) {
                        setTimeout(() => {
                            playSound('levelup');
                            triggerConfetti();
                            $('.pet-name-display-modal').text(gameContainer.find('.pet-name-display').text());
                            $('.new-level-display-modal').text(res.level);
                            $('#levelUpModal').modal('show');
                        }, 800);
                    }

                    // Unlock controls after the 2-second animation concludes
                    setTimeout(() => {
                        enableAllActions();
                    }, 2000);
                } else {
                    showReaction(res.message, 'danger');
                    enableAllActions();
                }
            },
            error: function() {
                showReaction('Item usage failed. Try again!', 'danger');
                enableAllActions();
            }
        });
    });

    // ============================================
    // 7. Navbar Notification Interactions (Moved to top for global scope)
    // ============================================
});
