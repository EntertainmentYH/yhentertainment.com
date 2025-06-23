/* ===================================================================
 * Ceevee 2.0.0 - Main JS
 *
 * ------------------------------------------------------------------- */

(function (html) {

    "use strict";

    html.className = html.className.replace(/\bno-js\b/g, '') + ' js ';

    /* Preloader
     * -------------------------------------------------- */
    const ssPreloader = function () {

        const preloader = document.querySelector('#preloader');
        if (!preloader) return;

        window.addEventListener('load', function () {

            document.querySelector('body').classList.remove('ss-preload');
            document.querySelector('body').classList.add('ss-loaded');

            preloader.addEventListener('transitionend', function (e) {
                if (e.target.matches("#preloader")) {
                    this.style.display = 'none';
                }
            });

        });

        // force page scroll position to top at page refresh
        // window.addEventListener('beforeunload' , function () {
        //     window.scrollTo(0, 0);
        // });

    }; // end ssPreloader


    /* Parallax
     * -------------------------------------------------- */
    const ssParallax = function () {

        const rellax = new Rellax('.rellax');

    }; // end ssParallax


    /* Move header menu
     * -------------------------------------------------- */
    const ssMoveHeader = function () {

        const hdr = document.querySelector('.s-header');
        const hero = document.querySelector('#hero');
        let triggerHeight;

        if (!(hdr && hero)) return;

        setTimeout(function () {
            triggerHeight = hero.offsetHeight - 170;
        }, 300);

        window.addEventListener('scroll', function () {

            let loc = window.scrollY;


            if (loc > triggerHeight) {
                hdr.classList.add('sticky');
            } else {
                hdr.classList.remove('sticky');
            }

            if (loc > triggerHeight + 20) {
                hdr.classList.add('offset');
            } else {
                hdr.classList.remove('offset');
            }

            if (loc > triggerHeight + 150) {
                hdr.classList.add('scrolling');
            } else {
                hdr.classList.remove('scrolling');
            }

        });

    }; // end ssMoveHeader


    /* Mobile Menu
     * ---------------------------------------------------- */
    const ssMobileMenu = function () {

        const toggleButton = document.querySelector('.s-header__menu-toggle');
        const headerNavWrap = document.querySelector('.s-header__nav-wrap');
        const siteBody = document.querySelector("body");

        if (!(toggleButton && headerNavWrap)) return;

        toggleButton.addEventListener('click', function (event) {
            event.preventDefault();
            toggleButton.classList.toggle('is-clicked');
            siteBody.classList.toggle('menu-is-open');
        });

        headerNavWrap.querySelectorAll('.s-header__nav a').forEach(function (link) {
            link.addEventListener("click", function (evt) {

                // at 800px and below
                if (window.matchMedia('(max-width: 800px)').matches) {
                    toggleButton.classList.toggle('is-clicked');
                    siteBody.classList.toggle('menu-is-open');
                }
            });
        });

        window.addEventListener('resize', function () {

            // above 800px
            if (window.matchMedia('(min-width: 801px)').matches) {
                if (siteBody.classList.contains('menu-is-open')) siteBody.classList.remove('menu-is-open');
                if (toggleButton.classList.contains("is-clicked")) toggleButton.classList.remove("is-clicked");
            }
        });

    }; // end ssMobileMenu


    /* Highlight active menu link on pagescroll
     * ------------------------------------------------------ */
    const ssScrollSpy = function () {

        const sections = document.querySelectorAll(".target-section");

        // Add an event listener listening for scroll
        window.addEventListener("scroll", navHighlight);

        function navHighlight() {

            // Get current scroll position
            let scrollY = window.pageYOffset;

            // Loop through sections to get height(including padding and border), 
            // top and ID values for each
            sections.forEach(function (current) {
                const sectionHeight = current.offsetHeight;
                const sectionTop = current.offsetTop - 50;
                const sectionId = current.getAttribute("id");

                /* If our current scroll position enters the space where current section 
                 * on screen is, add .current class to parent element(li) of the thecorresponding 
                 * navigation link, else remove it. To know which link is active, we use 
                 * sectionId variable we are getting while looping through sections as 
                 * an selector
                 */
                if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                    let link = document.querySelector(".s-header__nav a[href*=" + sectionId + "]");
                    if (link && link.parentNode) link.parentNode.classList.add("current");
                } else {
                    let link = document.querySelector(".s-header__nav a[href*=" + sectionId + "]");
                    if (link && link.parentNode) link.parentNode.classList.remove("current");
                }
            });
        }

    }; // end ssScrollSpy


    /* Swiper
     * ------------------------------------------------------ */
    const ssSwiper = function () {

        const mySwiper = new Swiper('.swiper-container', {

            slidesPerView: 1,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                // when window width is >= 401px
                401: {
                    slidesPerView: 1,
                    spaceBetween: 20
                },
                // when window width is >= 801px
                801: {
                    slidesPerView: 2,
                    spaceBetween: 48
                }
            }
        });

    }; // end ssSwiper


    /* Lightbox
     * ------------------------------------------------------ */
    const ssLightbox = function () {

        const folioLinks = document.querySelectorAll('.folio-item a');
        const modals = [];

        folioLinks.forEach(function (link) {
            let modalbox = link.getAttribute('href');
            let instance = basicLightbox.create(
                document.querySelector(modalbox),
                {
                    onShow: function (instance) {
                        //detect Escape key press
                        document.addEventListener("keydown", function (evt) {
                            evt = evt || window.event;
                            if (evt.keyCode === 27) {
                                instance.close();
                            }
                        });
                    }
                }
            )
            modals.push(instance);
        });

        folioLinks.forEach(function (link, index) {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                modals[index].show();
            });
        });

    };  // end ssLightbox


    /* Alert boxes
     * ------------------------------------------------------ */
    const ssAlertBoxes = function () {

        const boxes = document.querySelectorAll('.alert-box');

        boxes.forEach(function (box) {

            box.addEventListener('click', function (e) {
                if (e.target.matches(".alert-box__close")) {
                    e.stopPropagation();
                    e.target.parentElement.classList.add("hideit");

                    setTimeout(function () {
                        box.style.display = "none";
                    }, 500)
                }
            });

        })

    }; // end ssAlertBoxes


    /* Smoothscroll
     * ------------------------------------------------------ */
    const ssSmoothScroll = function () {

        const triggers = document.querySelectorAll(".smoothscroll");

        triggers.forEach(function (trigger) {
            trigger.addEventListener("click", function () {
                const target = trigger.getAttribute("href");

                Jump(target, {
                    duration: 1200,
                });
            });
        });

    }; // end ssSmoothScroll


    /* back to top
     * ------------------------------------------------------ */
    const ssBackToTop = function () {

        const pxShow = 900;
        const goTopButton = document.querySelector(".ss-go-top");

        if (!goTopButton) return;

        // Show or hide the button
        if (window.scrollY >= pxShow) goTopButton.classList.add("link-is-visible");

        window.addEventListener('scroll', function () {
            if (window.scrollY >= pxShow) {
                if (!goTopButton.classList.contains('link-is-visible')) goTopButton.classList.add("link-is-visible")
            } else {
                goTopButton.classList.remove("link-is-visible")
            }
        });

    }; // end ssBackToTop



    /* initialize
     * ------------------------------------------------------ */
    (function ssInit() {

        ssPreloader();
        ssParallax();
        ssMoveHeader();
        ssMobileMenu();
        ssScrollSpy();
        ssSwiper();
        ssLightbox();
        ssAlertBoxes();
        ssSmoothScroll();
        ssBackToTop();

    })();

})(document.documentElement);

fetch('https://www.ipplus360.com/getIP')
    .then(res => res.json())
    .then(data => {
        // 例如 data.country_code = "CN", "US", "TW" 等
        if (data && data.country_code) {
            document.cookie = "country_code=" + data.country_code + ";path=/";
        }
    });

// 投票系统 
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('vote-form');
    const result = document.getElementById('vote-result');
    if (form) {
        form.onsubmit = function (e) {
            e.preventDefault();
            const data = new FormData(form);
            fetch('vote.php', {
                method: 'POST',
                body: data
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'ok') {
                        result.innerHTML = '投票成功！';
                    } else {
                        result.innerHTML = res.msg;
                    }
                });
        };
    }
    // 显示结果函数（进度条样式）
    function showVoteResult(data) {
        let total = 0;
        for (let k in data) total += data[k];
        let html = '<div style="width:100%;max-width:500px">';
        for (let k in data) {
            let percent = total ? Math.round(data[k] / total * 100) : 0;
            html += `
                <div style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span>${k}</span>
                        <span style="font-size:0.95em;color:#555;">${data[k]}票 (${percent}%)</span>
                    </div>
                    <div style="background:#eee;border-radius:6px;overflow:hidden;height:20px;">
                        <div style="background:#00bfae;height:100%;width:${percent}%;transition:width 0.5s;"></div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }
    function alarm(msg) {
        alert(msg);
    }

    // 记录投票时间到localStorage
    function setVoteCooldown() {
        localStorage.setItem('voted_time', Date.now());
    }
    function getVoteCooldown() {
        return parseInt(localStorage.getItem('voted_time') || '0', 10);
    }
    function getCooldownLeft() {
        const last = getVoteCooldown();
        if (!last) return 0;
        const now = Date.now();
        const diff = now - last;
        const left = 10 * 60 * 1000 - diff; // 10分钟
        return left > 0 ? left : 0;
    }
    function formatTime(ms) {
        const s = Math.ceil(ms / 1000);
        const min = Math.floor(s / 60);
        const sec = s % 60;
        return `${min}分${sec}秒`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('vote-form');
        const result = document.getElementById('vote-result');
        const votedText = '已投票，10分钟内不可再次投票。';
        let timer = null;

        function updateCooldownUI() {
            const left = getCooldownLeft();
            if (left > 0) {
                form.style.display = 'none';
                result.innerHTML = `${votedText}<br>请等待 ${formatTime(left)} 后可再次投票。`;
                timer = setTimeout(updateCooldownUI, 1000);
            } else {
                form.style.display = '';
                result.innerHTML = '';
                if (timer) clearTimeout(timer);
            }
        }

        if (form) {
            // 页面加载时检查冷却
            if (getCooldownLeft() > 0) {
                updateCooldownUI();
            }
            form.onsubmit = function (e) {
                e.preventDefault();
                if (getCooldownLeft() > 0) {
                    updateCooldownUI();
                    return;
                }
                const data = new FormData(form);
                fetch('vote.php', {
                    method: 'POST',
                    body: data
                })
                    .then(res => res.json())
                    .then(res => {
                        if (res.status === 'ok') {
                            setVoteCooldown();
                            alarm('感谢您的投票！');
                            form.style.display = 'none';
                            result.innerHTML = '投票成功！<br>' + showVoteResult(res.data) + `<br>${votedText}`;
                            updateCooldownUI();
                        } else {
                            result.innerHTML = res.msg;
                        }
                    });
            };
        }
    });
});