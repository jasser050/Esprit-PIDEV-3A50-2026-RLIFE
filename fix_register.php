<?php
$path = __DIR__ . '/templates/pages/auth/register.html.twig';
$content = file_get_contents($path);
$n = str_replace("\r\n", "\n", $content);

// ── Step 2: replace wrong Face ID block with correct Preferences HTML ─────────
$step2Start = strpos($n, "                <!-- Step 2: Preferences -->\n                <div data-wizard-target=\"step\" class=\"hidden space-y-5\">");
if ($step2Start === false) { echo "step2 not found\n"; exit(1); }

// find closing </div> of step 2 div
$pos = $step2Start + strlen("                <!-- Step 2: Preferences -->\n                ");
$depth = 0; $len = strlen($n);
while ($pos < $len) {
    if (substr($n,$pos,4)==='<div') { $depth++; $pos+=4; }
    elseif (substr($n,$pos,6)==='</div>') { $depth--; $pos+=6; if($depth===0) break; }
    else $pos++;
}
$step2End = $pos;

$step2New = '                <!-- Step 2: Preferences -->
                <div data-wizard-target="step" class="hidden space-y-6">
                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Gender</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="gender" value="male" class="peer sr-only" checked required>
                                <div class="p-4 text-center bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 transition-colors">
                                    <i data-lucide="user" class="w-6 h-6 mx-auto mb-2 text-slate-400"></i>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Male</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="gender" value="female" class="peer sr-only" required>
                                <div class="p-4 text-center bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 transition-colors">
                                    <i data-lucide="user" class="w-6 h-6 mx-auto mb-2 text-slate-400"></i>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Female</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Study Level -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">What\'s your study level?</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="study_level" value="beginner" class="peer sr-only" checked>
                                <div class="p-4 text-center bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 transition-colors">
                                    <i data-lucide="sprout" class="w-6 h-6 mx-auto mb-2 text-slate-400"></i>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Beginner</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="study_level" value="intermediate" class="peer sr-only">
                                <div class="p-4 text-center bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 transition-colors">
                                    <i data-lucide="trending-up" class="w-6 h-6 mx-auto mb-2 text-slate-400"></i>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Intermediate</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="study_level" value="advanced" class="peer sr-only">
                                <div class="p-4 text-center bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 transition-colors">
                                    <i data-lucide="rocket" class="w-6 h-6 mx-auto mb-2 text-slate-400"></i>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Advanced</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Weekly Goal -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Weekly study goal: <span id="goal-display" class="text-primary-600 dark:text-primary-400">5 hours</span>
                        </label>
                        <input type="range" name="weekly_goal" min="1" max="10" value="5"
                            class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full appearance-none cursor-pointer accent-primary-600"
                            oninput="document.getElementById(\'goal-display\').textContent = this.value + \' hours\'">
                        <div class="flex justify-between text-xs text-slate-400 dark:text-slate-500 mt-1">
                            <span>1h</span><span>10h</span>
                        </div>
                    </div>

                    <!-- Interests -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">What are you interested in?</label>
                        <div class="flex flex-wrap gap-2">
                            {% set interests = [\'Web Dev\', \'Data Science\', \'Design\', \'Business\', \'Languages\', \'Math\', \'Science\', \'Arts\', \'Engineering\'] %}
                            {% for interest in interests %}
                            <label class="cursor-pointer">
                                <input type="checkbox" name="interests[]" value="{{ interest|lower|replace({\' \': \'_\'}) }}" class="peer sr-only">
                                <span class="inline-flex px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full border-2 border-transparent peer-checked:bg-primary-100 dark:peer-checked:bg-primary-900/30 peer-checked:text-primary-700 dark:peer-checked:text-primary-300 peer-checked:border-primary-300 dark:peer-checked:border-primary-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                    {{ interest }}
                                </span>
                            </label>
                            {% endfor %}
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="notifications" name="notifications" checked
                                class="mt-1 w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-50
