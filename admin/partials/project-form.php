<?php
/**
 * Shared project form partial.
 * Expects these variables from the including page:
 *   $editing    — null (add) or array (edit row from DB)
 *   $catalog    — Software::catalog()
 *   $selectedSw — array of checked software keys
 *   $mainCat    — string ('graphic'|'video')
 *   $mediaKind  — string ('gallery'|'video'|'link')
 *   $imgDir     — absolute path to uploads/images
 *   $csrf       — Csrf::token()
 */
use App\Project;
use App\Csrf;

$currentVideoType = $editing["video_type"] ?? "";
$hasExternalUrl   = !empty($editing["video_url"]);
$hasLocalFile     = !empty($editing["video_file"]);
?>

<form method="POST" enctype="multipart/form-data" id="projectForm" novalidate class="space-y-6">
    <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing["id"] ?>">
    <?php endif; ?>

    <!-- ══ SECTION 1 — Basic info ══ -->
    <div class="glass rounded-2xl p-6 space-y-4">
        <p class="form-section-label">
            <i class="fa-solid fa-circle-info"></i> Basic info
        </p>

        <div>
            <label class="label">Title <span class="text-rose-400">*</span></label>
            <input class="input" name="title" required
                   value="<?= htmlspecialchars($editing["title"] ?? "") ?>"
                   placeholder="Project / product name">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="label">Main category</label>
                <select name="main_category" id="mainCat" class="select">
                    <?php foreach (Project::MAIN_CATEGORIES as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $mainCat === $k ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lbl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Sub-category</label>
                <select name="sub_category" id="subCat" class="select"></select>
            </div>
        </div>

        <div>
            <label class="label">Display as</label>
            <div class="grid grid-cols-3 gap-3">
                <?php
                $kindMeta = [
                    "video"   => ["fa-circle-play",               "Video player"],
                    "gallery" => ["fa-images",                    "Lightbox"],
                    "link"    => ["fa-arrow-up-right-from-square", "External link"],
                ];
                foreach (Project::MEDIA_KINDS as $k => $lbl):
                    [$icon, $short] = $kindMeta[$k];
                    $active = $mediaKind === $k;
                ?>
                    <label class="kind-pill flex flex-col items-center gap-2 py-4 px-3 rounded-xl border cursor-pointer select-none transition"
                           data-kind="<?= $k ?>">
                        <input type="radio" name="media_kind" value="<?= $k ?>"
                               class="sr-only" <?= $active ? 'checked' : '' ?>>
                        <i class="fa-solid <?= $icon ?> text-base"></i>
                        <span class="text-[10px] font-semibold text-center leading-tight"><?= $short ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="label">Sort order</label>
                <input class="input" type="number" name="sort_order"
                       value="<?= (int)($editing["sort_order"] ?? 0) ?>" min="0">
            </div>
            <div>
                <label class="label">Status</label>
                <select name="is_published" class="select">
                    <option value="1" <?= !isset($editing) || $editing["is_published"] ? 'selected' : '' ?>>✅ Published</option>
                    <option value="0" <?= isset($editing) && !$editing["is_published"]  ? 'selected' : '' ?>>⏸ Draft</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ══ SECTION 2 — Cover image ══ -->
    <div class="glass rounded-2xl p-6 space-y-4">
        <p class="form-section-label">
            <i class="fa-solid fa-image"></i> Cover image
        </p>

        <?php if ($editing && !empty($editing["image"])):
            $isUrl  = str_starts_with((string)$editing["image"], "http");
            $isFile = file_exists($imgDir . "/" . $editing["image"]);
            $cSrc   = $isUrl ? $editing["image"]
                     : ($isFile ? '/uploads/images/' . $editing["image"] : '/assets/images/' . $editing["image"]);
        ?>
            <div class="rounded-xl overflow-hidden border border-white/8 bg-black/30">
                <img src="<?= htmlspecialchars($cSrc) ?>" class="w-full max-h-48 object-cover"
                     onerror="this.parentElement.style.display='none'" alt="Current cover">
                <div class="px-3 py-2 text-[10px] text-white/35 truncate border-t border-white/5">
                    <?= htmlspecialchars($editing["image"]) ?>
                </div>
            </div>
        <?php endif; ?>

        <div>
            <label class="label">
                <?= ($editing && !empty($editing["image"])) ? 'Replace cover image' : 'Upload cover image' ?>
            </label>
            <input type="file" name="image" id="coverInput" class="input" accept="image/*">
            <p class="text-[10px] text-white/35 mt-1">JPG · PNG · WebP · GIF · max 8 MB</p>
            <div id="coverPreview" class="hidden mt-2 rounded-xl overflow-hidden border border-white/8 bg-black/30">
                <img id="coverPreviewImg" src="" class="w-full max-h-40 object-cover" alt="Preview">
                <div class="px-3 py-2 text-[10px] text-white/35" id="coverPreviewName"></div>
            </div>
        </div>
    </div>

    <!-- ══ SECTION 3 — Video source (shown only for media_kind=video) ══ -->
    <div id="videoSection" class="glass rounded-2xl p-6 space-y-4">
        <div class="flex items-center justify-between">
            <p class="form-section-label mb-0">
                <i class="fa-solid fa-circle-play"></i> Video source
            </p>
            <?php if ($editing && ($hasExternalUrl || $hasLocalFile)): ?>
                <span class="text-[10px] px-2.5 py-1 rounded-full border font-semibold
                    <?= $currentVideoType === "external"
                        ? "bg-rose-500/15 border-rose-400/30 text-rose-200"
                        : "bg-indigo-500/15 border-indigo-400/30 text-indigo-200" ?>">
                    <i class="fa-solid <?= $currentVideoType === "external" ? "fa-youtube" : "fa-film" ?> mr-1"></i>
                    Using: <?= $currentVideoType === "external" ? "External URL" : "Local file" ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="flex items-start gap-2.5 px-3 py-2.5 rounded-xl bg-amber-500/8 border border-amber-400/20">
            <i class="fa-solid fa-circle-info text-amber-400 mt-0.5 shrink-0 text-xs"></i>
            <p class="text-[11px] text-amber-100/70 leading-relaxed">
                <strong class="text-amber-200">Priority:</strong>
                External URL is always used when set (embedded via iframe).
                The local file is used only as fallback when no external URL is provided.
            </p>
        </div>

        <!-- External URL (priority) -->
        <div class="rounded-xl border border-white/10 bg-black/25 p-4 space-y-3">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded bg-rose-500/20 border border-rose-400/30 flex items-center justify-center shrink-0">
                    <i class="fa-brands fa-youtube text-rose-300 text-[10px]"></i>
                </span>
                <span class="text-sm font-semibold text-white">External Video URL</span>
                <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/15 border border-rose-400/25 text-rose-300">PRIORITY</span>
            </div>
            <p class="text-[11px] text-white/40">YouTube, Vimeo, or any embeddable URL. Auto-converted to embed format on save.</p>
            <div>
                <label class="label">Video URL</label>
                <input class="input" name="video_url" id="videoUrlInput"
                       value="<?= htmlspecialchars($editing["video_url"] ?? "") ?>"
                       placeholder="https://youtu.be/... or https://www.youtube.com/watch?v=...">
            </div>
            <?php if ($hasExternalUrl): ?>
                <div class="flex items-center gap-2 p-2.5 rounded-lg bg-black/30 border border-white/6">
                    <i class="fa-brands fa-youtube text-rose-400 text-sm shrink-0"></i>
                    <span class="text-[10px] text-white/50 truncate flex-1"><?= htmlspecialchars($editing["video_url"]) ?></span>
                    <span class="text-[9px] text-emerald-300 font-semibold shrink-0"><i class="fa-solid fa-check"></i> Saved</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Local file (fallback) -->
        <div class="rounded-xl border border-white/8 bg-black/20 p-4 space-y-3">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-film text-indigo-300 text-[10px]"></i>
                </span>
                <span class="text-sm font-semibold text-white">Local Video File</span>
                <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/15 border border-indigo-400/25 text-indigo-300">FALLBACK</span>
                <span class="text-[10px] text-white/30">max 50 MB</span>
            </div>
            <p class="text-[11px] text-white/40">Self-hosted file. Used only when no External URL is set above.</p>

            <?php if ($editing && $hasLocalFile): ?>
                <div class="rounded-lg overflow-hidden border border-white/8 bg-black">
                    <video src="/uploads/videos/<?= htmlspecialchars($editing["video_file"]) ?>"
                           <?= !empty($editing["video_poster"]) ? 'poster="/uploads/images/'.htmlspecialchars($editing["video_poster"]).'"' : '' ?>
                           class="w-full max-h-40" controls preload="metadata"></video>
                </div>
                <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-black/30 border border-white/6">
                    <span class="text-[10px] text-white/55 truncate flex items-center gap-1.5">
                        <i class="fa-solid fa-file-video text-indigo-400"></i>
                        <?= htmlspecialchars($editing["video_file"]) ?>
                    </span>
                    <form method="POST" class="shrink-0"
                          onsubmit="return confirm('Permanently delete this video file?')"
                          action="/admin/projects-edit.php?id=<?= (int)$editing['id'] ?>">
                        <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                        <input type="hidden" name="action" value="delete_video">
                        <input type="hidden" name="id"     value="<?= (int)$editing["id"] ?>">
                        <button class="btn btn-danger text-xs py-1"><i class="fa-solid fa-trash"></i> Remove</button>
                    </form>
                </div>
                <div>
                    <label class="label">Replace video file</label>
                    <input type="file" name="video_file" id="videoFileInput" class="input"
                           accept="video/mp4,video/webm,video/quicktime,video/ogg,.mp4,.webm,.mov,.m4v,.ogg,.ogv">
                </div>
            <?php else: ?>
                <div>
                    <label class="label">Upload video file</label>
                    <input type="file" name="video_file" id="videoFileInput" class="input"
                           accept="video/mp4,video/webm,video/quicktime,video/ogg,.mp4,.webm,.mov,.m4v,.ogg,.ogv">
                    <p class="text-[10px] text-white/35 mt-1">MP4 or WebM recommended · max 50 MB</p>
                </div>
            <?php endif; ?>

            <div id="videoProgress" class="hidden space-y-1">
                <div class="flex justify-between text-[10px] text-white/50">
                    <span>Uploading video…</span><span id="videoPct">0%</span>
                </div>
                <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                    <div id="videoBar" class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-300" style="width:0%"></div>
                </div>
            </div>

            <hr class="border-white/6">

            <!-- Poster -->
            <?php if ($editing && !empty($editing["video_poster"])): ?>
                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-black/30 border border-white/6">
                    <img src="/uploads/images/<?= htmlspecialchars($editing["video_poster"]) ?>"
                         class="w-20 h-12 object-cover rounded border border-white/10 shrink-0"
                         onerror="this.style.display='none'" alt="">
                    <div class="flex-1 min-w-0">
                        <div class="text-[10px] text-white/55 truncate"><?= htmlspecialchars($editing["video_poster"]) ?></div>
                        <form method="POST" class="mt-2"
                              action="/admin/projects-edit.php?id=<?= (int)$editing['id'] ?>"
                              onsubmit="return confirm('Delete the poster image?')">
                            <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="delete_poster">
                            <input type="hidden" name="id"     value="<?= (int)$editing["id"] ?>">
                            <button class="btn btn-danger text-xs py-1"><i class="fa-solid fa-trash"></i> Remove poster</button>
                        </form>
                    </div>
                </div>
                <div>
                    <label class="label">Replace poster</label>
                    <input type="file" name="video_poster" class="input" accept="image/*">
                </div>
            <?php else: ?>
                <div>
                    <label class="label">Poster / thumbnail <span class="text-white/35 font-normal text-[10px]">before playback</span></label>
                    <input type="file" name="video_poster" class="input" accept="image/*">
                    <p class="text-[10px] text-white/35 mt-1">Falls back to cover image if not set.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ SECTION 4 — Skills & tools ══ -->
    <div class="glass rounded-2xl p-6 space-y-4">
        <p class="form-section-label"><i class="fa-solid fa-layer-group"></i> Skills &amp; tools</p>

        <div>
            <label class="label">Core stack &amp; capabilities</label>
            <input class="input" name="skills_used"
                   value="<?= htmlspecialchars($editing["skills_used"] ?? "") ?>"
                   placeholder="REST APIs, analytics, system design, performance tuning…">
        </div>

        <div>
            <label class="label">Technology stack</label>
            <div class="grid grid-cols-3 gap-1.5 max-h-44 overflow-y-auto p-2 rounded-xl bg-black/30 border border-white/8 sidebar-scroll">
                <?php foreach ($catalog as $key => [$lab, $let, $col, $bg]):
                    $checked = in_array($key, $selectedSw, true);
                ?>
                    <label class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg cursor-pointer hover:bg-white/5 transition <?= $checked ? 'bg-white/5' : '' ?>"
                           title="<?= htmlspecialchars($lab) ?>">
                        <input type="checkbox" name="software[]" value="<?= $key ?>"
                               <?= $checked ? 'checked' : '' ?> class="accent-purple-500">
                        <span class="text-[9px] font-bold w-5 h-5 rounded-md flex items-center justify-center shrink-0"
                              style="background:<?= $bg ?>;color:<?= $col ?>">
                            <?= htmlspecialchars($let) ?>
                        </span>
                        <span class="text-[11px] truncate"><?= htmlspecialchars($lab) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ══ SECTION 5 — Details ══ -->
    <div class="glass rounded-2xl p-6 space-y-4">
        <p class="form-section-label"><i class="fa-solid fa-align-left"></i> Details</p>

        <div>
            <label class="label">Description</label>
            <textarea name="description" class="textarea" rows="4"
                      placeholder="What problem was solved, what shipped, and what the stack was…"><?= htmlspecialchars($editing["description"] ?? "") ?></textarea>
        </div>

        <div>
            <label class="label">Project link <span class="text-white/35 font-normal text-[10px]">optional</span></label>
            <input class="input" name="project_url"
                   value="<?= htmlspecialchars($editing["project_url"] ?? "") ?>"
                   placeholder="https://..."><!-- Product or demo URL -->
        </div>
    </div>

    <!-- ══ Submit ══ -->
    <div class="flex gap-3">
        <button type="submit" id="submitBtn" class="btn btn-primary flex-1 justify-center text-sm py-3">
            <i class="fa-solid fa-save" id="submitIcon"></i>
            <span id="submitLabel"><?= $editing ? "Save changes" : "Create project" ?></span>
        </button>
        <a href="/admin/projects.php" class="btn btn-ghost py-3" title="Back to all projects">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </div>
</form>

<style>
.form-section-label {
    display:flex; align-items:center; gap:.45rem;
    font-size:.7rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; color:rgba(255,255,255,.28);
}
.kind-pill { border-color:rgba(255,255,255,.08); background:rgba(0,0,0,.22); color:rgba(255,255,255,.45); }
.kind-pill:hover { border-color:rgba(255,255,255,.18); color:rgba(255,255,255,.75); }
.kind-pill.active { border-color:rgba(168,85,247,.55); background:rgba(168,85,247,.12); color:#fff; box-shadow:0 0 0 1px rgba(168,85,247,.25); }
</style>

<script>
(function () {
    const SUBS       = <?= json_encode(Project::SUB_CATEGORIES) ?>;
    const currentSub = <?= json_encode($editing["sub_category"] ?? "") ?>;
    const initKind   = <?= json_encode($mediaKind) ?>;

    const mainSel   = document.getElementById('mainCat');
    const subSel    = document.getElementById('subCat');
    const videoSec  = document.getElementById('videoSection');
    const kindPills = document.querySelectorAll('.kind-pill');
    const kindRadios= document.querySelectorAll('input[name="media_kind"]');
    const form      = document.getElementById('projectForm');

    function rebuildSubs(preserve) {
        const list = SUBS[mainSel.value] || [];
        subSel.innerHTML = '';
        if (!list.length) {
            const o = document.createElement('option'); o.value=''; o.textContent='— none —'; subSel.appendChild(o); return;
        }
        list.forEach(function(lbl){
            const o = document.createElement('option'); o.value=lbl; o.textContent=lbl;
            if (preserve && lbl===preserve) o.selected=true;
            subSel.appendChild(o);
        });
    }

    function getKind() {
        let v='gallery'; kindRadios.forEach(function(r){if(r.checked)v=r.value;}); return v;
    }

    function syncKind() {
        const cur = getKind();
        kindPills.forEach(function(el){ el.classList.toggle('active', el.dataset.kind===cur); });
        if (videoSec) videoSec.style.display = cur==='video' ? '' : 'none';
    }

    let userChoseKind = false;
    kindPills.forEach(function(el){
        el.addEventListener('click', function(){
            const r=el.querySelector('input[type="radio"]'); if(r)r.checked=true;
            userChoseKind=true; syncKind();
        });
    });

    mainSel.addEventListener('change', function(){
        rebuildSubs(null);
        if (!userChoseKind) {
            const sug = mainSel.value==='video'?'video':'gallery';
            kindRadios.forEach(function(r){r.checked=(r.value===sug);}); syncKind();
        }
    });

    const coverInput  = document.getElementById('coverInput');
    const coverPreview= document.getElementById('coverPreview');
    const coverImg    = document.getElementById('coverPreviewImg');
    const coverName   = document.getElementById('coverPreviewName');
    if (coverInput) {
        coverInput.addEventListener('change', function(){
            const f=coverInput.files[0];
            if(!f){if(coverPreview)coverPreview.classList.add('hidden');return;}
            if(coverImg) coverImg.src=URL.createObjectURL(f);
            if(coverName) coverName.textContent=f.name+' ('+(f.size/1024/1024).toFixed(2)+' MB)';
            if(coverPreview) coverPreview.classList.remove('hidden');
        });
    }

    const VIDEO_MAX=50*1024*1024, IMG_MAX=8*1024*1024;
    function checkSizes(){
        const v=document.getElementById('videoFileInput');
        if(v&&v.files[0]&&v.files[0].size>VIDEO_MAX){
            alert('Video is larger than 50 MB. Please choose a smaller file.'); v.value=''; return false;
        }
        if(coverInput&&coverInput.files[0]&&coverInput.files[0].size>IMG_MAX){
            alert('Cover image is larger than 8 MB. Please choose a smaller image.');
            coverInput.value=''; if(coverPreview)coverPreview.classList.add('hidden'); return false;
        }
        return true;
    }

    const submitBtn  =document.getElementById('submitBtn');
    const submitIcon =document.getElementById('submitIcon');
    const submitLabel=document.getElementById('submitLabel');
    const progWrap   =document.getElementById('videoProgress');
    const progBar    =document.getElementById('videoBar');
    const progPct    =document.getElementById('videoPct');

    function setSubmitting(uploading){
        if(!submitBtn)return;
        submitBtn.disabled=true;
        if(submitIcon)  submitIcon.className='fa-solid fa-spinner fa-spin';
        if(submitLabel) submitLabel.textContent=uploading?'Uploading…':'Saving…';
    }

    if (form) {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            if(!checkSizes())return;
            const vInput=document.getElementById('videoFileInput');
            if(!vInput||!vInput.files[0]){ setSubmitting(false); form.submit(); return; }
            setSubmitting(true);
            if(progWrap) progWrap.classList.remove('hidden');
            const xhr=new XMLHttpRequest();
            xhr.upload.addEventListener('progress',function(ev){
                if(!ev.lengthComputable)return;
                const p=Math.round(ev.loaded/ev.total*100);
                if(progBar) progBar.style.width=p+'%';
                if(progPct) progPct.textContent=p+'%';
            });
            xhr.addEventListener('load',function(){
                if(xhr.status>=200&&xhr.status<400){window.location.href=xhr.responseURL||'/admin/projects.php';}
                else{
                    submitBtn.disabled=false;
                    if(submitIcon)submitIcon.className='fa-solid fa-save';
                    if(submitLabel)submitLabel.textContent=<?= json_encode($editing ? 'Save changes' : 'Create project') ?>;
                    if(progWrap)progWrap.classList.add('hidden');
                    alert('Upload failed (HTTP '+xhr.status+'). Please try again.');
                }
            });
            xhr.addEventListener('error',function(){
                submitBtn.disabled=false;
                if(submitIcon)submitIcon.className='fa-solid fa-save';
                if(submitLabel)submitLabel.textContent=<?= json_encode($editing ? 'Save changes' : 'Create project') ?>;
                if(progWrap)progWrap.classList.add('hidden');
                alert('Network error. Check your connection and try again.');
            });
            xhr.open('POST', form.action||window.location.href);
            xhr.send(new FormData(form));
        });
    }

    rebuildSubs(currentSub);
    syncKind();
})();
</script>
