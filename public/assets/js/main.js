/*----------------------------------------------------------------
    Index Of All Plugin Here
------------------------------------------------------------------
    @version         : 1.1.0
    @Template Name   : Billora
    @Template author : Theme29

    :: WOW Animation active       
    :: Swiper Slider Active
    :: Responsive Menubar
    :: Plan Toggle Switch

/*-------------------------------------------------
    :: WOW active
-------------------------------------------------*/
new WOW().init();

/*----------------------------------------------
    :: Plan Toggle Switch
----------------------------------------------*/
let isYearly = false;
document.querySelectorAll(".toggle-option").forEach((option) => {
    option.addEventListener("click", () => {
        isYearly = option.textContent.trim().toLowerCase() === "yearly";
        updatePlanVisibility();
        updateActiveClass(option);
    });
});

function updateActiveClass(option) {
    document.querySelectorAll(".toggle-option").forEach((opt) => {
        opt.classList.remove("active");
    });
    option.classList.add("active");
}

function updatePlanVisibility() {
    const plans = document.querySelectorAll('[id^="plan-"]');
    plans.forEach((plan) => {
        const planFrequency = plan.getAttribute("data-frequency").toLowerCase();
        if (planFrequency === (isYearly ? "yearly" : "monthly")) {
            plan.style.display = "block";
        } else {
            plan.style.display = "none";
        }
    });
}

updatePlanVisibility();
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".toggle-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const planId = this.dataset.planId;
            const list = document.getElementById("feature-list-" + planId);
            const icon = this.querySelector("i");
            const span = this.querySelector("span");

            list.classList.toggle("collapsed");
            list.classList.toggle("expanded");

            const anyExpanded =
                document.querySelectorAll(".feature-list.expanded").length > 0;

            if (list.classList.contains("expanded")) {
                span.textContent = "Hide Feature";
                icon.classList.remove("iconoir-nav-arrow-down");
                icon.classList.add("iconoir-nav-arrow-up");
            } else {
                span.textContent = "More Feature";
                icon.classList.remove("iconoir-nav-arrow-up");
                icon.classList.add("iconoir-nav-arrow-down");
            }
        });
    });
});

/*----------------------------------------------
    :: Swiper Slider Active
----------------------------------------------*/
const swiper = new Swiper(".testimonial-slider-active", {
    slidesPerView: 3,
    spaceBetween: 24,
    loop: true,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    breakpoints: {
        0: {
            slidesPerView: 1,
        },
        768: {
            slidesPerView: 2,
        },
        1200: {
            slidesPerView: 3,
        },
    },
});
/*-------------------------------------------------
    :: Responsive Menubar
-------------------------------------------------*/
const navbarMenu = document.getElementById("navbarMenu");
const toggleBtn = document.getElementById("toggleBtn");
toggleBtn.addEventListener("click", () => {
    navbarMenu.classList.toggle("is-open");
});

window.addEventListener("resize", () => {
    if (window.innerWidth > 991) {
        navbarMenu.classList.remove("is-open");
    }
});
