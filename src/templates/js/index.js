$(function () {

    var swiper1 = new Swiper(
        ".banner-parallax-slider",
        {
            effect: "fade",
            autoplay: {
                delay: 4000,
            },
            autoHeight: true,
            loop: true,
        }
    );

    var swiper2 = new Swiper(
        ".project-slide",
        {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            navigation: {
                prevEl: ".project-slider-button-prev",
                nextEl: ".project-slider-button-next",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
        });


    var swiper3 = new Swiper(
        ".project-slide-2",
        {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            navigation: {
                prevEl: ".project-slider-button-prev-2",
                nextEl: ".project-slider-button-next-2",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
        });

    const brand = new Swiper(".brand-active", {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        roundLengths: true,
        pagination: {
            clickable: true,
        },
        autoplay: {
            delay: 3000,
        },
        breakpoints: {
            992: {
                slidesPerView: 4,
            },
            768: {
                slidesPerView: 3,
            },
            576: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        },
    });

    const brand1 = new Swiper(".brand-active1", {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        roundLengths: true,
        clickable: true,
        autoplay: {
            delay: 3000,
        },
        breakpoints: {
            1200: {
                slidesPerView: 5,
            },
            992: {
                slidesPerView: 4,
            },
            768: {
                slidesPerView: 3,
            },
            576: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        },
    });

    $("[data-background]").each(function () {
        $(this).css(
            "background-image",
            "url( " + $(this).attr("data-background") + "  )"
        );
    });

    if ($(".slider-hover-item li").length) {
        $(".slider-hover-item li").each(function () {
            let self = $(this);

            self.on("mouseenter", function () {
                $(".slider-hover-item li").removeClass("active");
                $(this).addClass("active");
            });
        });
    }
})