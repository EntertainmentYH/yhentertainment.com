/* ===================================================================
 * Guest Message System - AJAX handlers
 * Handles: submit, reply, edit, delete, captcha
 * =================================================================== */

(function () {
    "use strict";

    document.addEventListener('DOMContentLoaded', function () {

        // ===== Character Counters =====
        function setupCharCounter(inputId, countId, maxLen) {
            var input = document.getElementById(inputId);
            var counter = document.getElementById(countId);
            if (!input || !counter) return;
            function update() {
                var len = input.value.length;
                counter.textContent = len + '/' + maxLen;
                counter.style.color = len > maxLen * 0.9 ? '#e74c3c' : '#888';
            }
            input.addEventListener('input', update);
            update();
        }
        setupCharCounter('guestTitle', 'titleCount', 150);
        setupCharCounter('guestSearch', 'searchCount', 500);

        // ===== Toast Helper =====
        function showToast(message, kind, duration) {
            kind = kind || 'info';
            duration = duration || 3000;
            var container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
            var t = document.createElement('div');
            t.className = 'toast ' + (kind === 'success' ? 'toast-success' : 'toast-error');
            t.textContent = message;
            t.style.transform = 'translateX(120%)';
            t.style.opacity = '0';
            container.appendChild(t);
            // animate in
            requestAnimationFrame(function () {
                t.style.transform = 'translateX(0)';
                t.style.opacity = '1';
            });
            // auto dismiss
            setTimeout(function () {
                t.style.transform = 'translateX(120%)';
                t.style.opacity = '0';
                t.addEventListener('transitionend', function rm() {
                    t.removeEventListener('transitionend', rm);
                    if (t.parentNode) t.parentNode.removeChild(t);
                });
                setTimeout(function () { try { t.remove(); } catch(e){} }, 500);
            }, duration);
            // click to dismiss
            t.addEventListener('click', function () {
                t.style.transform = 'translateX(120%)';
                t.style.opacity = '0';
                setTimeout(function () { try { t.remove(); } catch(e){} }, 400);
            });
        }

        // ===== AJAX Helper =====
        function ajaxPost(data, onSuccess, onError) {
            var formData = new FormData();
            // Always include CSRF token
            var csrfEl = document.querySelector('input[name="csrf_token"]');
            if (csrfEl) formData.append('csrf_token', csrfEl.value);
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    formData.append(key, data[key]);
                }
            }
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (result.ok) {
                    if (onSuccess) onSuccess(result);
                } else {
                    var msg = '';
                    var err = result.error || '';
                    if (err === 'wrong_captcha') msg = '验证码错误，请重新输入计算结果。';
                    else if (err === 'missing_fields') msg = '请填写所有必填项。';
                    else if (err === 'rate_limited') msg = '操作过于频繁，请稍后再试。';
                    else if (err === 'edit_window_expired') msg = '编辑窗口已过（5分钟内可编辑）。';
                    else if (err === 'not_owner') msg = '你只能编辑自己的留言。';
                    else if (err === 'message_not_found') msg = '留言未找到。';
                    else if (err === 'parent_not_found') msg = '原留言未找到。';
                    else msg = '操作失败：' + err;
                    showToast(msg, 'error', 4000);
                    if (onError) onError(result);
                }
            })
            .catch(function (e) {
                showToast('网络错误，请稍后重试。', 'error', 4000);
                if (onError) onError({ error: 'network' });
                console.error('AJAX error:', e);
            });
        }

        var guestForm = document.getElementById('guestForm');
        if (guestForm) {
            guestForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var title = guestForm.querySelector('[name="title"]').value.trim();
                var search = guestForm.querySelector('[name="search"]').value.trim();
                var userId = guestForm.querySelector('[name="user_id"]').value.trim();
                var captcha = guestForm.querySelector('[name="captcha"]').value.trim();
                var captchaHash = guestForm.querySelector('[name="captcha_hash"]').value;

                if (!search || !userId) {
                    showToast('请填写昵称与留言内容。', 'error');
                    return;
                }
                if (!captcha) {
                    showToast('请填写验证码。', 'error');
                    return;
                }

                var submitBtn = guestForm.querySelector('.guest-submit');
                var origText = submitBtn.textContent;
                submitBtn.textContent = '提交中...';
                submitBtn.disabled = true;

                ajaxPost({
                    action: 'submit_message',
                    title: title,
                    search: search,
                    user_id: userId,
                    captcha: captcha,
                    captcha_hash: captchaHash
                }, function (result) {
                    showToast('留言发布成功！', 'success');
                    // Reload page to show the new message properly
                    // Save scroll position
                    try { sessionStorage.setItem('yh_scroll', Math.round(window.scrollY || 0)); } catch (e) {}
                    setTimeout(function () { location.reload(); }, 800);
                }, function () {
                    submitBtn.textContent = origText;
                    submitBtn.disabled = false;
                    // Clear captcha input on failure
                    var capInput = guestForm.querySelector('[name="captcha"]');
                    if (capInput) { capInput.value = ''; capInput.focus(); }
                });
            });
        }

        // ===== Toggle Reply Form =====
        document.addEventListener('click', function (e) {
            var replyBtn = e.target.closest('.guest-reply-btn');
            if (!replyBtn) return;
            var msgId = replyBtn.getAttribute('data-msg-id');
            if (!msgId) return;
            var form = document.getElementById('reply-form-' + msgId);
            if (!form) return;
            var isHidden = form.style.display === 'none';
            // Close all other open forms first
            document.querySelectorAll('.guest-reply-form, .guest-edit-form').forEach(function (f) {
                f.style.display = 'none';
            });
            form.style.display = isHidden ? 'block' : 'none';
            if (isHidden) {
                form.querySelector('textarea').focus();
            }
        });

        // ===== Toggle Edit Form =====
        document.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.guest-edit-btn');
            if (!editBtn) return;
            var msgId = editBtn.getAttribute('data-msg-id');
            if (!msgId) return;
            var form = document.getElementById('edit-form-' + msgId);
            if (!form) return;
            var isHidden = form.style.display === 'none';
            // Close all other open forms
            document.querySelectorAll('.guest-reply-form, .guest-edit-form').forEach(function (f) {
                f.style.display = 'none';
            });
            form.style.display = isHidden ? 'block' : 'none';
            if (isHidden) {
                form.querySelector('textarea').focus();
            }
        });

        // ===== Cancel buttons =====
        document.addEventListener('click', function (e) {
            var cancelBtn = e.target.closest('.guest-cancel');
            if (!cancelBtn) return;
            var msgId = cancelBtn.getAttribute('data-msg-id');
            if (!msgId) return;
            // Hide both reply and edit forms for this message
            var replyForm = document.getElementById('reply-form-' + msgId);
            var editForm = document.getElementById('edit-form-' + msgId);
            if (replyForm) replyForm.style.display = 'none';
            if (editForm) editForm.style.display = 'none';
        });

        // ===== Reply Submit =====
        document.addEventListener('click', function (e) {
            var submitBtn = e.target.closest('.guest-reply-submit');
            if (!submitBtn) return;
            var msgId = submitBtn.getAttribute('data-msg-id');
            if (!msgId) return;
            var form = document.getElementById('reply-form-' + msgId);
            if (!form) return;

            var userIdInput = form.querySelector('[name^="reply_user_id_"]');
            var searchInput = form.querySelector('[name^="reply_search_"]');
            var captchaInput = form.querySelector('[name^="reply_captcha_"]');

            var userId = userIdInput ? userIdInput.value.trim() : '';
            var search = searchInput ? searchInput.value.trim() : '';
            var captcha = captchaInput ? captchaInput.value.trim() : '';

            if (!search || !userId) {
                showToast('请填写昵称与回复内容。', 'error');
                return;
            }
            if (!captcha) {
                showToast('请填写验证码。', 'error');
                return;
            }

            var origText = submitBtn.textContent;
            submitBtn.textContent = '提交中...';
            submitBtn.disabled = true;

            ajaxPost({
                action: 'reply_message',
                parent_id: msgId,
                user_id: userId,
                search: search,
                captcha: captcha
            }, function (result) {
                showToast('回复成功！', 'success');
                try { sessionStorage.setItem('yh_scroll', Math.round(window.scrollY || 0)); } catch (e) {}
                setTimeout(function () { location.reload(); }, 800);
            }, function () {
                submitBtn.textContent = origText;
                submitBtn.disabled = false;
                if (captchaInput) { captchaInput.value = ''; captchaInput.focus(); }
            });
        });

        // ===== Edit Save =====
        document.addEventListener('click', function (e) {
            var saveBtn = e.target.closest('.guest-edit-save');
            if (!saveBtn) return;
            var msgId = saveBtn.getAttribute('data-msg-id');
            if (!msgId) return;
            var form = document.getElementById('edit-form-' + msgId);
            if (!form) return;

            var titleInput = form.querySelector('[name^="edit_title_"]');
            var searchInput = form.querySelector('[name^="edit_search_"]');

            var title = titleInput ? titleInput.value.trim() : '';
            var search = searchInput ? searchInput.value.trim() : '';

            if (!search) {
                showToast('留言内容不能为空。', 'error');
                return;
            }

            var origText = saveBtn.textContent;
            saveBtn.textContent = '保存中...';
            saveBtn.disabled = true;

            ajaxPost({
                action: 'edit_message',
                message_id: msgId,
                title: title,
                search: search
            }, function (result) {
                showToast('编辑成功！', 'success');
                try { sessionStorage.setItem('yh_scroll', Math.round(window.scrollY || 0)); } catch (e) {}
                setTimeout(function () { location.reload(); }, 800);
            }, function () {
                saveBtn.textContent = origText;
                saveBtn.disabled = false;
            });
        });

        // ===== Delete =====
        document.addEventListener('click', function (e) {
            var deleteBtn = e.target.closest('.guest-delete-btn');
            if (!deleteBtn) return;

            // Don't intercept if it's inside a traditional form (non-AJAX fallback)
            if (deleteBtn.closest('form') && !deleteBtn.hasAttribute('data-msg-id')) return;

            var msgId = deleteBtn.getAttribute('data-msg-id');
            if (!msgId) return;

            if (!confirm('确认删除你的留言？')) return;

            var origText = deleteBtn.textContent;
            deleteBtn.textContent = '删除中...';
            deleteBtn.disabled = true;

            ajaxPost({
                action: 'delete_message',
                delete_id: msgId
            }, function (result) {
                showToast('删除成功！', 'success');
                // Remove the message from DOM
                var msgEl = document.getElementById('msg-' + msgId);
                // The msg-id might be inside the data attribute on the wrapper
                if (!msgEl) {
                    msgEl = document.querySelector('[data-msg-id="' + msgId + '"]');
                }
                if (msgEl) {
                    // Also remove the following hr
                    var nextHr = msgEl.nextElementSibling;
                    if (nextHr && nextHr.tagName === 'HR') {
                        nextHr.remove();
                    }
                    msgEl.remove();
                }
            }, function () {
                deleteBtn.textContent = origText;
                deleteBtn.disabled = false;
            });
        });

        // ===== Restore scroll position after reload =====
        (function () {
            try {
                var saved = sessionStorage.getItem('yh_scroll');
                if (saved) {
                    var y = parseInt(saved, 10);
                    if (!isNaN(y) && y > 0) {
                        window.scrollTo(0, y);
                    }
                    sessionStorage.removeItem('yh_scroll');
                }
            } catch (e) {}
        })();
    });
})();