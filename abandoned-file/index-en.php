<?php
// 读取配置
$config_file = __DIR__ . '/configuration.json';
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
}

// 统计文件路径
$statistics_dir = __DIR__ . '/statistics';
if (!is_dir($statistics_dir))
    mkdir($statistics_dir, 0777, true);

$daily_counter_file = $statistics_dir . '/daily_counter.json';
$total_counter_file = $statistics_dir . '/total_counter.json';
$online_file = $statistics_dir . '/online.txt';

// 日期
$today = date('Y-m-d');

// ========== 单日访问量 ==========
$daily_data = [];
if (file_exists($daily_counter_file)) {
    $daily_data = json_decode(file_get_contents($daily_counter_file), true);
    if (!is_array($daily_data))
        $daily_data = [];
}
if (!isset($daily_data[$today])) {
    $daily_data[$today] = 1;
} else {
    $daily_data[$today]++;
}
file_put_contents($daily_counter_file, json_encode($daily_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$today_count = $daily_data[$today];

// ========== 总访问量 ==========
$total_count = 0;
if (file_exists($total_counter_file)) {
    $total_count = (int) file_get_contents($total_counter_file);
}
$total_count++;
file_put_contents($total_counter_file, $total_count);

// ========== 在线人数 ==========
$timeout = 300; // 5分钟
$ip = $_SERVER['REMOTE_ADDR'];
$now = time();
$onlines = [];
if (file_exists($online_file)) {
    $onlines = file($online_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}
$new_onlines = [];
$found = false;
foreach ($onlines as $line) {
    list($online_ip, $last_time) = explode('|', $line);
    if ($now - $last_time < $timeout) {
        if ($online_ip == $ip) {
            $new_onlines[] = "$ip|$now";
            $found = true;
        } else {
            $new_onlines[] = "$online_ip|$last_time";
        }
    }
}
if (!$found) {
    $new_onlines[] = "$ip|$now";
}
file_put_contents($online_file, implode("\n", $new_onlines));
$online_count = count($new_onlines);

// ========== 网站运行时长 ==========
$site_days = '';
if (!empty($config['site_start_date'])) {
    $site_days = '1';
    $start = strtotime($config['site_start_date']);
    $now = strtotime(date('Y-m-d'));

    if ($start && $now >= $start) {
        $site_days = '2';
        $site_days = floor(($now - $start) / 86400) + 1;

    }
}
?>

<!DOCTYPE html>
<html class="no-js" lang="en">

<head>

    <!--- basic page needs
    ================================================== -->
    <meta charset="utf-8">
    <title>Entertainment_YH</title>
    <meta name="description" content="Entertainment_YH's Personal Website.">
    <meta name="author" content="Entertainment_YH">
    <meta name="keywords"
        content="Entertainment_YH,YH,YH的网站,YH Community,娱乐之神,YH娱乐之神,娱乐,个人网站,网站,个人,Entertainment,_,Entertainment_YH,God of Entertainment,Entertainment_YH个人网站,娱乐之神的个人网站,娱乐之神的网站">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- mobile specific metas
    ================================================== -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/vendor.css">

    <!-- script
    ================================================== -->
    <script defer src="js/vendor/fontawesome-free-6.7.2-web/js/all.js"></script>

    <!-- favicons
    ================================================== -->
    <link rel="apple-touch-icon" href="/favicon_io/apple-touch-icon.png">
    <link rel="android-touch-icon" href="/favicon_io/android-touch-icon.png">
    <link rel="icon" type="image/png" href="/favicon_io/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon_io/favicon-16x16.png" sizes="16x16">

</head>

<body id="top" class="ss-preload">
    <!-- translation
     ================================================= -->
    <div id="top"></div>


    <!-- preloader
    ================================================== -->
    <div id="preloader">
        <div id="loader"></div>
    </div>


    <!-- header
    ================================================== -->
    <header class="s-header">
        <div class="row s-header__nav-wrap">
            <nav class="s-header__nav">
                <ul>
                    <li class="current"><a class="smoothscroll" href="#hero">index</a></li>
                    <li><a class="smoothscroll" href="#about">about</a></li>
                    <li><a class="smoothscroll" href="#resume">announcement & others</a></li>
                    <li><a class="smoothscroll" href="#portfolio">photos</a></li>
                    <li><a class="smoothscroll" href="#testimonials">website contributor</a></li>
                </ul>
            </nav>
        </div> <!-- end row -->

        <a class="s-header__menu-toggle" href="#0" title="Menu">
            <span class="s-header__menu-icon"></span>
        </a>
    </header> <!-- end s-header -->


    <!-- hero
    ================================================== -->
    <section id="hero" class="s-hero target-section">

        <div class="s-hero__bg rellax" data-rellax-speed="-7"></div>

        <div class="row s-hero__content">
            <div class="column">

                <div class="s-hero__content-about">

                    <h1>Hello, I'm Entertainment_YH</h1>

                    <h3>
                        Hello, I am Entertainment_YH, also known as 'Yule_YH' or 'YH'. This website is YH's personal
                        website, used for more people to learn about me, share about me, and interact with me. Welcome
                        to my website!
                    </h3>

                    <div class="s-hero__content-social">
                        <a href="https://space.bilibili.com/1977333915?spm_id_from=333.1007.0.0"><i
                                class="fa-brands fa-bilibili" aria-hidden="true"></i></a>
                        <a href="https://github.com/EntertainmentYH/yhentertainment.com"><i
                                class="fa-brands fa-square-github" aria-hidden="true"></i></a>
                        <a href="https://steamcommunity.com/id/Entertainment_YH/"><i class="fab fa-steam"
                                aria-hidden="true"></i></a>
                        <a href="https://www.youtube.com/@Entertainment_CHINESE"><i class="fab fa-youtube"
                                aria-hidden="true"></i></a>
                        <a href="https://x.com/Entertainm15252"><i class="fab fa-square-x-twitter"
                                aria-hidden="true"></i></a>
                    </div>

                </div> <!-- end s-hero__content-about -->

            </div>
        </div> <!-- s-hero__content -->

        <div class="s-hero__scroll">
            <a href="#about" class="s-hero__scroll-link smoothscroll">
                <span class="scroll-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        style="fill:rgba(0, 0, 0, 1);">
                        <path
                            d="M18.707 12.707L17.293 11.293 13 15.586 13 6 11 6 11 15.586 6.707 11.293 5.293 12.707 12 19.414z">
                        </path>
                    </svg>
                </span>
                <span class="scroll-text">Let's get started!</span>
            </a>
        </div> <!-- s-hero__scroll -->

    </section> <!-- end s-hero -->


    <!-- about
    ================================================== -->
    <section id="about" class="s-about target-section">

        <div class="row">
            <div class="column large-3 tab-12">
                <img class="s-about__pic" src="/images/yhs/icon.jpg" alt="">
            </div>
            <div class="column large-9 tab-12 s-about__content">
                <h3>About this website</h3>
                <p>
                    &emsp;&emsp;This website was created by me on March 30<sup>th</sup>, 2025. I hope it can become an
                    interesting place to record the bits and pieces of my life and to let more people know about my
                    basic information. At the same time, I welcome feedback from everyone at any time, and I will keep
                    updating the website content to make it better and better. Everyone is welcome to visit!
                </p>

                <hr>

                <div class="row s-about__content-bottom">
                    <div class="column w-1000-stack">
                        <h3>Website History</h3>
                        <p>&emsp;&emsp;As mentioned above, this website was created on March 30, 2025, but it is not the
                            first website of Entertainment_YH! The author first learned HTML in 2024, when they needed
                            to prepare for the high school entrance exam, so there wasn't much learning in the early
                            stages of the website. As of now (March 31<sup>th</sup>, 2025), the author is currently in
                            the first year of high school, so updates may be slow, but the author promises that over
                            time, the website's content will become more and more abundant. (Of course, if you want to
                            see the original website, it is currently lying in the previous project, and it may be
                            publicly displayed in the future (as a negative teaching material), but for now, it is
                            considered a draft because the author has constantly attempted new codes and features
                            without seriously learning the foundational basic code, resulting in a website that is
                            neither aesthetically pleasing nor free of bugs, which is why this version exists.)
                        </p>
                    </div>
                </div>

                <hr>

                <div class="row s-about__content-bottom">
                    <div class="column w-1000-stack">
                        <h3>Contact me</h3>
                        <p>My email:&emsp;14899034@qq.com
                            <br \>
                            My QID:&emsp;123YHawa
                            <br \>
                            Of course, the software icons on the website can also directly link to my homepage when
                            clicked.
                        </p>
                    </div>
                </div>
            </div>
        </div> <!-- end row -->

    </section> <!-- end s-about -->


    <!-- resume
    ================================================== -->
    <section id="resume" class="s-resume target-section">

        <div class="row s-resume__section">
            <div class="column large-3 tab-12">
                <h3 class="section-header-allcaps">announcement</h3>
            </div>
            <div class="column large-9 tab-12">
                <div class="resume-block">

                    <div class="resume-block__header">
                        <h4 class="h3">Future website update plan</h4>
                        <p class="resume-block__header-meta">
                            <span>Entertainment_YH</span>
                            <span class="resume-block__header-date">
                                May 11<sup>th</sup>, 2025 - Present
                            </span>
                        </p>
                    </div>

                    <p>
                    <ul>
                        <li>
                            Continue to develop new features or modules.
                        </li>
                        <li>
                            Optimize user experience.
                        </li>
                        <li>
                            Assemble some fun things.
                        </li>
                    </ul>
                    </p>

                </div> <!-- end resume-block -->
            </div>
        </div> <!-- post-section -->

        <div class="row s-resume__section">
            <div class="column large-3 tab-12">
                <h3 class="section-header-allcaps">post</h3>
            </div>
            <div class="column large-9 tab-12">
                <div class="resume-block">

                    <div class="resume-block__header">
                        <h4 class="h3">My first post</h4>
                        <p class="resume-block__header-meta">
                            <span>Entertainment_YH</span>
                            <span class="resume-block__header-date">
                                Published on May 17<sup>th</sup>, 2025
                            </span>
                        </p>
                    </div>

                    <p>
                        This is my first news, and it is also the test news of the website, this column will put some
                        interesting things in the future, similar to "QQ space" or "WeChat circle of friends", and will
                        also reprint some interesting things, so stay tuned!
                    </p>

                    <div class="resume-block__header">
                        <h4 class="h3">An interesting question</h4>
                        <p class="resume-block__header-meta">
                            <span>Entertainment_YH</span>
                            <span class="resume-block__header-date">
                                Published on May 23<sup>th</sup>, 2025
                            </span>
                        </p>
                    </div>

                    <p>
                        Imagine this: you have a remote control that can reset anything, it can reset anything, whether it objectively exists or doesn't exist. For example, resetting a relationship with a person will be like a stranger after the reset; Or a broken glass that's reset and returns to the timeline before it was broken. What will you do with this remote? (Can only be reset once, invalid after one time)
                    </p>

                    <div class="resume-block__header">
                        <h4 class="h3">Apology Letter</h4>
                        <p class="resume-block__header-meta">
                            <span>Entertainment_YH</span>
                            <span>&bull; 355 characters</span>
                            <span class="resume-block__header-date">
                                Published on May 28<sup>th</sup>, 2025
                            </span>
                        </p>
                    </div>

                    <p>
                        Respected Teacher:<br \>
                        Hello!<br \>
                        &emsp;&emsp;I write this letter of reflection with deep feelings of guilt and regret in order to contemplate the mistakes made during today's geography class.<br \>
                        &emsp;&emsp;This afternoon's geography class was ineffective due to my failure to prepare the necessary materials in a timely manner. I deeply apologize for this. Such behavior not only shows a lack of respect for the teacher but also reflects my own irresponsibility. I did not cherish the valuable opportunity to learn in class.
                        <br \>
                        &emsp;&emsp;At the same time, this geography lesson also reflects my attitude towards learning geography. If I had valued the geography lesson, this incident would not have occurred. I have come to a profound realization that I cannot neglect the subject of geography simply because it has a higher score; such an approach will only lead to a decline in my overall performance.
                        <br \>
                        &emsp;&emsp;Furthermore, I have also recognized that my actions may have had a certain negative impact on the learning atmosphere of the class. As a student, I should influence my peers positively with a proactive attitude and good behavior, rather than bringing about adverse effects on the class due to my negligence. I would like to express my apologies to all my classmates and promise to actively improve my behavior in order to contribute to fostering a positive learning environment.
                        <br \>
                        &emsp;&emsp;Finally, I deeply appreciate the hard work and dedication of my teacher. In order to foster our growth and progress, you have invested countless efforts. I will certainly cherish every learning opportunity you provide and demonstrate my changes and progress through tangible actions. Moreover, I would like to express my profound apologies to my teacher, not only for the issues today but also for my previous disregard for geography. I am determined to correct my attitude towards learning from this moment on, to prepare for geography lessons in advance, to complete geography assignments diligently, and to actively participate in geography class discussions to improve my geography grades. I hereby guarantee that from now on, I will value every subject at school and will firmly refrain from making similar mistakes.
                        <br \>
                        &emsp;&emsp;Yours sincerely,
                        <br \>
                        May 28, 2025
                    </p>

                </div> <!-- post-section -->

            </div>
        </div>


        <div class="row s-resume__section">
            <div class="column large-3 tab-12">
                <h3 class="section-header-allcaps">partner</h3>
            </div>
            <div class="column large-9 tab-12">
                <div class="resume-block">

                    <div class="resume-block__header">
                        <h4 class="h3">Voident_Game</h4>
                        <p class="resume-block__header-meta">
                            <span>Voident_Game Studio</span>
                            <span class="resume-block__header-date">
                                Established in Jun, 2022
                            </span>
                        </p>
                    </div>

                    <p>
                        *Here is the introduction of the studio.*
                    </p>

                </div> <!-- end resume-block -->

                <div class="resume-block">

                    <div class="resume-block__header">
                        <h4 class="h4">About Voident_Game</h4>
                        <p class="resume-block__header-meta">
                            <span>Voident_Game Studio</span>
                            <span class="resume-block__header-date">
                                Established in Jun, 2022
                            </span>
                        </p>
                    </div>

                    <p>
                        *This is where the Minecraft server of this studio is placed.*
                    </p>

                </div> <!-- end resume-block -->
            </div>
        </div> <!-- s-resume__section -->


        <div class="row s-resume__section">
            <div class="column large-3 tab-12">
                <h3 class="section-header-allcaps">language<br \>localization</h3>
            </div>
            <div class="column large-9 tab-12">
                <div class="resume-block">

                    <p>
                        All translations are machine translations and may be inaccurate. Please refer to the original
                        text in Simplified Chinese for comparison.
                    </p>

                    <ul class="skill-bars-fat">
                        <li>
                            <div class="progress percent100"></div>
                            <strong>
                                    简体中文/people's republic of china
                            </strong>
                        </li>
                        <li>
                            <div class="progress percent85"></div>
                            <strong>繁體中文/hong kong, taiwan, macau (PRC)</strong>
                        </li>
                        <li>
                            <div class="progress percent85"></div>
                            <strong style="color:rgb(55, 177, 55)">
                                english/united states
                            </strong>
                        </li>
                        <li>
                            <div class="progress percent5"></div>
                            <strong>Русский язык/russian federation</strong>
                        </li>
                        <li>
                            <div class="progress percent5"></div>
                            <strong>Deutsch/germany</strong>
                        </li>
                        <li>
                            <div class="progress percent5"></div>
                            <strong>日本語/japan</strong>
                        </li>
                        <li>
                            <div class="progress percent5"></div>
                            <strong>한국어/republic of korea</strong>
                        </li>
                        <li>
                            <div class="progress percent5"></div>
                            <strong>uygur/xinjiang, PRC</strong>
                        </li>
                    </ul>

                </div> <!-- end resume-block -->

            </div>
        </div> <!-- s-resume__section -->

    </section> <!-- end s-resume -->


    <!-- portfolio
    ================================================== -->
    <section id="portfolio" class="s-portfolio target-section">

        <div class="row s-portfolio__header">
            <div class="column large-12">
                <h3>Photos</h3>
            </div>
        </div>

        <div class="row collapse block-large-1-4 block-medium-1-3 block-tab-1-2 block-500-stack folio-list">

            <div class="column folio-item">
                <a href="#modal-01" class="folio-item__thumb">
                    <img src="/images/yhs/laifu.jpg" srcset="/images/yhs/laifu.jpg" alt="">
                </a>
            </div> <!-- end folio-item -->

            <div class="column folio-item">
                <a href="#modal-02" class="folio-item__thumb">
                    <img src="/images/yhs/7430562FEACC6D1C7AE435774265C090.png"
                        srcset="/images/yhs/7430562FEACC6D1C7AE435774265C090.png" alt="">
                </a>
            </div> <!-- end folio-item -->

            <div class="column folio-item">
                <a href="#modal-03" class="folio-item__thumb">
                    <img src="/images/yhs/D899347890A7B9B8AE0587A353697366.jpg"
                        srcset="/images/yhs/D899347890A7B9B8AE0587A353697366.jpg" alt="">
                </a>
            </div> <!-- end folio-item -->

            <div class="column folio-item">
                <a href="#modal-04" class="folio-item__thumb">
                    <img src="/images/yhs/thunder.jpg" srcset="/images/yhs/thunder.jpg" alt="">
                </a>
            </div> <!-- end folio-item -->

        </div> <!-- end folio-list -->


        <!-- Modal Templates Popup
        =========================================================== -->
        <div id="modal-01" hidden>
            <div class="modal-popup">
                <img src="/images/yhs/laifu.jpg" alt="来福" />

                <div class="modal-popup__desc">
                    <h5>来福(Lai Fu)</h5>
                    <p>This is a puppy that the author of this website raised from 2021 to 2022. It is a silver fox.
                        Unfortunately, it was given away due to disturbance to the neighbors, and the author feels very
                        reluctant about this. Therefore, this is a tribute to Lai Fu.</p>
                    <ul class="modal-popup__cat">
                        <li>June 7, 2022</li>
                        <li>Animal</li>
                        <li>Gongyi City, Zhengzhou City, Henan Province, China</li>
                    </ul>
                </div>

            </div>
        </div> <!-- end modal -->

        <div id="modal-02" hidden>
            <div class="modal-popup">
                <img src="/images/yhs/7430562FEACC6D1C7AE435774265C090.png" alt="" />

                <div class="modal-popup__desc">
                    <h5>Xianmi National Forest Park</h5>
                    <p>A beautiful snow-capped mountain is located in the Xianmi National Forest Park, QiLian Mountain
                        Range. I was fortunate enough to visit it twice in 2023, and honestly, it amazed me. At an
                        altitude of 3400 meters, it stands tall at the boundary line of China's first and second steps,
                        symbolizing the grandeur of the Tibetan Plateau.</p>
                    <ul class="modal-popup__cat">
                        <li>October 29, 2023</li>
                        <li>Natural scenery</li>
                        <li>Xianmi National Forest Park, Gansu Province, China</li>
                    </ul>
                </div>

            </div>
        </div> <!-- end modal -->

        <div id="modal-03" hidden>
            <div class="modal-popup">
                <img src="/images/yhs/D899347890A7B9B8AE0587A353697366.jpg" alt="" />

                <div class="modal-popup__desc">
                    <h5>Taklamakan Desert</h5>
                    <p>Situated in the depths of Asia, before the towering stone tablet, one gazes far and wide, where
                        the Taklamakan Desert confronts us in its most pristine form. The vast sea of sand merges with
                        the sky in the distance, where solitary smoke and the setting sun complement each other. The
                        sand dunes resemble frozen surges, each ripple engraved with eons of wind and rain, while the
                        withered Huayang narrates the rise and fall of the ancient Kingdom of Loulan. ( Contributed by
                        Fallen Star )</p>
                    <ul class="modal-popup__cat">
                        <li>September 30, 2023</li>
                        <li>Natural scenery</li>
                        <li>Aral City, Aksu Prefecture, Xinjiang Uyghur Autonomous Region, China</li>
                    </ul>
                </div>

            </div>
        </div> <!-- end modal -->

        <div id="modal-04" hidden>
            <div class="modal-popup">
                <img src="/images/yhs/thunder.jpg" alt="" />

                <div class="modal-popup__desc">
                    <h5>Lightning</h5>
                    <p>The lightning I captured by accidentally (not actual accidentally :P ) but some people always say
                        it's a photoshopped picture??</p>
                    <ul class="modal-popup__cat">
                        <li>August 20, 2024</li>
                        <li>Natural scenery</li>
                        <li>Gongyi City, Zhengzhou City, Henan Province, China</li>
                    </ul>
                </div>

            </div>
        </div> <!-- end modal -->


    </section> <!-- end s-portfolio -->


    <!-- testimonials
    ================================================== -->
    <section id="testimonials" class="s-testimonials target-section">

        <div class="s-testimonials__bg"></div>

        <div class="row s-testimonials__header">
            <div class="column large-12">
                <h3>Website contributor</h3>
            </div>
        </div>

        <div class="row s-testimonials__content">

            <div class="column">

                <div class="swiper-container testimonial-slider">

                    <div class="swiper-wrapper">

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="//q1.qlogo.cn/g?b=qq&nk=3329261270&s=100" alt="Author"
                                    class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong>Entertainment_YH</strong>
                                    <span>Author</span>
                                </cite>
                            </div>
                            <p>
                                The author of this website has nothing to write about, right?
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="//q1.qlogo.cn/g?b=qq&nk=439751420&s=100" alt="Contributor"
                                    class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong>QN</strong>
                                    <span>Founder of Voident_Game Studio</span>
                                </cite>
                            </div>
                            <p>
                                The provider of this website's server, thank you for your contribution to this website!
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="/images/contributor/fallen-star.jpeg" alt="Contributor"
                                    class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong>Fallen Star</strong>
                                    <span>A friend of the author</span>
                                </cite>
                            </div>
                            <p>
                                Thank you to the editors of the text in the Photos for your contribution to this
                                website!
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                        <div class="testimonial-slider__slide swiper-slide">
                            <div class="testimonial-slider__author">
                                <img src="/images/contributor/you.png" alt="YOU" class="testimonial-slider__avatar">
                                <cite class="testimonial-slider__cite">
                                    <strong>All visitors of this website</strong>
                                    <span>Dedicated to the only you in the world</span>
                                </cite>
                            </div>
                            <p>
                                Thank you for visiting this website!
                            </p>
                        </div> <!-- end testimonial-slider__slide -->

                    </div> <!-- end testimonial slider swiper-wrapper -->

                    <div class="swiper-pagination"></div>

                </div> <!-- end swiper-container -->

            </div> <!-- end column -->

        </div> <!-- end row -->

    </section> <!-- end s-testimonials -->


    <!-- footer
    ================================================== -->
    <footer class="s-footer">
        <div class="row">
            <div class="column large-4 medium-6 w-1000-stack s-footer__social-block">
                <ul class="s-footer__social">
                    <li><a href="https://space.bilibili.com/1977333915?spm_id_from=333.1007.0.0"><i
                                class="fa-brands fa-bilibili" aria-hidden="true"></i></a></li>
                    <li><a href="https://github.com/EntertainmentYH/yhentertainment.com"><i
                                class="fa-brands fa-square-github" aria-hidden="true"></i></a></li>
                    <li><a href="https://steamcommunity.com/id/Entertainment_YH/"><i class="fab fa-steam"
                                aria-hidden="true"></i></a></li>
                    <li><a href="https://www.youtube.com/@Entertainment_CHINESE"><i class="fab fa-youtube"
                                aria-hidden="true"></i></a></li>
                    <li><a href="https://x.com/Entertainm15252"><i class="fab fa-square-x-twitter"
                                aria-hidden="true"></i></a></li>
                </ul>
            </div>

            <div class="column large-7 medium-6 w-1000-stack ss-copyright">
                <span>The final interpretation of this website is owned by Entertainment_YH.</span>
                <span><a target="_blank" href="" title="备案号">*Record number*</a></span>
            </div>

            <div class="column large-12 medium-8 w-1000-stack ss-statistics">
                <span>Today, the website had <?php echo $today_count; ?> visitors.</span>
                <span>A total of <?php echo $total_count; ?> people have been here.</span>
                <span>There are <?php echo $online_count; ?> people checking out my website right now.</span>
                <span>This website has been with you through <?php echo $site_days; ?> days and nights</span>
            </div>
        </div>

        <div class="ss-go-top">
            <a class="smoothscroll" title="Back to Top" href="#top">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M6 4h12v2H6zm5 10v6h2v-6h5l-6-6-6 6z" />
                </svg>
            </a>
        </div> <!-- end ss-go-top -->
    </footer>


    <!-- Java Script
    ================================================== -->
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>

</body>

</html>