// ======================================================
// MUNICIPALITY OF CERVANTES
// Main JavaScript
// Part 1
// ======================================================

document.addEventListener("DOMContentLoaded", () => {

    // ======================================================
    // UTILITY FUNCTIONS
    // ======================================================

    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => document.querySelectorAll(selector);

    // ======================================================
    // DROPDOWN NAVIGATION
    // ======================================================

    const dropdowns = $$(".dropdown");

    function closeAllDropdowns() {

        dropdowns.forEach(dropdown => {

            dropdown.classList.remove("active");
            dropdown.classList.remove("hovered");
            dropdown.classList.remove("sticky");

            if (dropdown.dropdownTimer) {
                clearTimeout(dropdown.dropdownTimer);
            }

        });

    }

    dropdowns.forEach(dropdown => {

        const button =
            dropdown.querySelector(".dropdown-btn") ||
            dropdown.querySelector(".dropdown-toggle");

        if (!button) return;

        // Hover Open
        dropdown.addEventListener("mouseenter", () => {

            if (!dropdown.classList.contains("sticky")) {

                dropdown.classList.add("hovered");

            }

        });

        // Hover Leave
        dropdown.addEventListener("mouseleave", () => {

            dropdown.classList.remove("hovered");

        });

        // Click Open
        button.addEventListener("click", (e) => {

            e.preventDefault();
            e.stopPropagation();

            const opened = dropdown.classList.contains("sticky") || dropdown.classList.contains("active");

            closeAllDropdowns();

            if (!opened) {

                dropdown.classList.add("sticky");
                dropdown.classList.add("active");

                dropdown.dropdownTimer = setTimeout(() => {

                    dropdown.classList.remove("sticky");
                    dropdown.classList.remove("hovered");
                    dropdown.classList.remove("active");

                }, 5000);

            }

        });

    });

    document.addEventListener("click", (e) => {

        if (!e.target.closest(".dropdown")) {

            closeAllDropdowns();

        }

    });

    // ======================================================
    // MOBILE MENU
    // ======================================================

    const hamburger = $(".hamburger");
    const navLinks = $(".nav-links");

    if (hamburger && navLinks) {

        hamburger.addEventListener("click", (e) => {

            e.stopPropagation();
            hamburger.classList.toggle("active");
            navLinks.classList.toggle("active");

        });

        // Close mobile menu when clicking outside
        document.addEventListener("click", (e) => {

            if (!e.target.closest(".navbar")) {

                hamburger.classList.remove("active");
                navLinks.classList.remove("active");

            }

        });

    }

    window.toggleMenu = function () {

        const navMenu =
            $(".nav-menu") ||
            $(".nav-left");

        if (!navMenu) return;

        navMenu.classList.toggle("active");

    };

    // ======================================================
    // ACCOMMODATION MODAL
    // ======================================================

    const accommodationModal = $("#modal");

    if (accommodationModal) {

        const modalImage = $("#modalImage");
        const modalTitle = $("#modalTitle");
        const modalLocation = $("#modalLocation");
        const modalRate = $("#modalRate");
        const modalDescription = $("#modalDescription");
        const closeButton = accommodationModal.querySelector(".close-btn");

        function closeAccommodationModal() {

            accommodationModal.classList.remove("show");
            document.body.style.overflow = "auto";

        }

        $$(".view-btn").forEach(button => {

            button.addEventListener("click", function () {

                modalImage.src = this.dataset.image;
                modalTitle.textContent = this.dataset.title;
                modalLocation.textContent = this.dataset.location;
                modalRate.textContent = this.dataset.rate;
                modalDescription.textContent = this.dataset.description;

                accommodationModal.classList.add("show");

                document.body.style.overflow = "hidden";

            });

        });

        closeButton.addEventListener("click", closeAccommodationModal);

        accommodationModal.addEventListener("click", (e) => {

            if (e.target === accommodationModal) {

                closeAccommodationModal();

            }

        });

        document.addEventListener("keydown", (e) => {

            if (
                e.key === "Escape" &&
                accommodationModal.classList.contains("show")
            ) {

                closeAccommodationModal();

            }

        });

        accommodationModal
            .querySelector(".modal-content")
            .addEventListener("click", (e) => {

                e.stopPropagation();

            });

    }
    // ======================================================
    // MAYOR INFORMATION MODAL
    // ======================================================

    const infoModal = $("#infoModal");

    if (infoModal) {

        const infoModalTitle = $("#modalTitle");
        const infoModalText = $("#modalText");
        const infoModalIcon = infoModal.querySelector(".modal-icon-container");
        const infoCloseBtn = infoModal.querySelector(".modal-close-btn");

        // ----------------------------------------------
        // DATA
        // ----------------------------------------------

        const contentData = {

            "View Profile": {

                iconHtml: `
                    <img
                        src="https://tse1.mm.bing.net/th/id/OIP.YNr_SYktStEzQN7ChwfglgHaFP?pid=Api&P=0&h=180"
                        alt="Mayor"
                        style="
                            width:90px;
                            height:90px;
                            border-radius:50%;
                            object-fit:cover;
                            border:3px solid #0d6efd;
                        ">
                `,

                title: "Profile of the Municipal Mayor",

                text:
                "The Municipal Mayor of Cervantes leads the local government by promoting transparent governance, sustainable development, quality public services, and programs that improve the welfare of every resident."

            },

            "Learn More": {

                iconHtml: `
                    <div style="
                        width:90px;
                        height:90px;
                        border-radius:50%;
                        background:#0d6efd;
                        color:#fff;
                        display:flex;
                        justify-content:center;
                        align-items:center;
                        font-size:42px;">
                        🏛️
                    </div>
                `,

                title: "Office of the Municipal Mayor",

                text:
                "The Office of the Municipal Mayor supervises all executive functions of the Municipality, ensuring efficient implementation of government programs, infrastructure projects, public safety initiatives, tourism development, and social services."

            },

            "View Updates": {

                iconHtml: `
                    <div style="
                        width:90px;
                        height:90px;
                        border-radius:50%;
                        background:#198754;
                        color:white;
                        display:flex;
                        justify-content:center;
                        align-items:center;
                        font-size:42px;">
                        ℹ️
                    </div>
                `,

                title: "Latest Municipal Updates",

                text:
                "Stay informed with the latest announcements, municipal activities, infrastructure developments, public advisories, and upcoming community events organized by the Municipality of Cervantes."

            }

        };

        // ----------------------------------------------
        // OPEN MODAL
        // ----------------------------------------------

        $$(".btn-blue").forEach(button => {

            button.addEventListener("click", function (e) {

                e.stopPropagation();

                const key = this.textContent.trim();

                const data = contentData[key];

                if (!data) return;

                infoModalIcon.innerHTML = data.iconHtml;

                infoModalTitle.textContent = data.title;

                infoModalText.textContent = data.text;

                infoModal.classList.add("show");

                document.body.style.overflow = "hidden";

            });

        });

        // ----------------------------------------------
        // CLOSE FUNCTION
        // ----------------------------------------------

        function closeInfoModal() {

            infoModal.classList.remove("show");

            document.body.style.overflow = "auto";

        }

        // ----------------------------------------------
        // EVENTS
        // ----------------------------------------------

        infoCloseBtn.addEventListener("click", closeInfoModal);

        infoModal.addEventListener("click", function (e) {

            if (e.target === infoModal) {

                closeInfoModal();

            }

        });

        document.addEventListener("keydown", function (e) {

            if (
                e.key === "Escape" &&
                infoModal.classList.contains("show")
            ) {

                closeInfoModal();

            }

        });

    }
        // ======================================================
    // TOURISM SLIDER
    // ======================================================

    const slideContainer = $(".slide");
    const nextBtn = $(".next");
    const prevBtn = $(".prev");

    if (slideContainer && nextBtn && prevBtn) {

        nextBtn.addEventListener("click", () => {

            const items = slideContainer.querySelectorAll(".item");

            if (items.length > 0) {

                slideContainer.appendChild(items[0]);

            }

        });

        prevBtn.addEventListener("click", () => {

            const items = slideContainer.querySelectorAll(".item");

            if (items.length > 0) {

                slideContainer.prepend(items[items.length - 1]);

            }

        });

    }


    // ======================================================
    // TOURISM DESTINATION MODAL
    // ======================================================

    const tourismModal = $("#tourismModal");

    if (tourismModal && slideContainer) {

        const tourismImage = $("#modalImage");
        const tourismTitle = $("#modalTitle");
        const tourismText = $("#modalText");
        const tourismCloseBtn = tourismModal.querySelector(".modal-close-btn");

        // --------------------------------------------------
        // DESTINATION DATABASE
        // --------------------------------------------------

        const destinationData = {

            "Red Soil": {

                img: "https://www.rngph.com/wp-content/uploads/2021/05/186528767_481615279735582_3959088796353590631_n-1.jpeg",

                title: "The Pilipil Red Soil",

                text:
                    "Located in Barangay Malaya, the famous Red Soil landscape offers an extraordinary natural attraction. Visitors enjoy scenic mountain views, photography, sightseeing, and off-road adventures."

            },

            "Bessang Pass Monument": {

                img: "https://upload.wikimedia.org/wikipedia/commons/5/57/Bessang_Pass_Monument.jpg",

                title: "Bessang Pass Natural Monument",

                text:
                    "A historical landmark commemorating the bravery of Filipino soldiers during World War II. Visitors experience panoramic mountain scenery together with an important chapter of Philippine history."

            },

            "New Zealand": {

                img: "https://images.unsplash.com/photo-1483982258113-b72862e6cff6?q=80&w=1200",

                title: "Cinematic Mountain Landscape",

                text:
                    "Experience breathtaking landscapes featuring lush green mountains, peaceful surroundings, and unforgettable panoramic views suitable for nature lovers."

            },

            "Japan": {

                img: "https://images.unsplash.com/photo-1477346611705-65d1883cee1e?q=80&w=1200",

                title: "Traditional Cultural Destination",

                text:
                    "Discover peaceful architecture, beautiful gardens, and cultural heritage inspired by timeless traditional landscapes."

            }

        };

        // --------------------------------------------------
        // OPEN DESTINATION MODAL
        // --------------------------------------------------

        slideContainer.addEventListener("click", (e) => {

            if (!e.target.classList.contains("explore-btn")) return;

            e.stopPropagation();

            const card = e.target.closest(".item");

            if (!card) return;

            const destinationName =
                card.querySelector(".name")?.textContent.trim();

            const data = destinationData[destinationName];

            if (!data) return;

            if (tourismImage.tagName === "IMG") {

                tourismImage.src = data.img;

            } else {

                tourismImage.style.backgroundImage = `url('${data.img}')`;

            }

            tourismTitle.textContent = data.title;
            tourismText.textContent = data.text;

            tourismModal.classList.add("show");

            document.body.style.overflow = "hidden";

        });

        // --------------------------------------------------
        // CLOSE DESTINATION MODAL
        // --------------------------------------------------

        function closeTourismModal() {

            tourismModal.classList.remove("show");

            document.body.style.overflow = "auto";

        }

        tourismCloseBtn.addEventListener("click", closeTourismModal);

        tourismModal.addEventListener("click", (e) => {

            if (e.target === tourismModal) {

                closeTourismModal();

            }

        });

        document.addEventListener("keydown", (e) => {

            if (
                e.key === "Escape" &&
                tourismModal.classList.contains("show")
            ) {

                closeTourismModal();

            }

        });

    }


    // ======================================================
    // GLOBAL WINDOW CLICK
    // ======================================================

    window.addEventListener("click", (e) => {

        if (!e.target.closest(".dropdown")) {

            closeAllDropdowns();

        }

    });


    // ======================================================
    // END OF SCRIPT
    // ======================================================

});