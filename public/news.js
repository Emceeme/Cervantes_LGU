// =====================================
// PUBLIC NEWS JAVASCRIPT
// Municipality of Cervantes
// =====================================

const modal = document.getElementById("newsModal");
const gallery = document.getElementById("modalGallery");
const modalTitle = document.getElementById("modalTitle");
const modalDate = document.getElementById("modalDate");
const modalContent = document.getElementById("modalContent");
const closeBtn = document.querySelector(".close");

/*
=====================================
OPEN NEWS
=====================================
*/

function openNews(title, content, image, date){

    // Title
    modalTitle.textContent = title;

    // Date
    modalDate.textContent = date;

    // Content
    modalContent.innerHTML = content.replace(/\n/g,"<br>");

    // Clear previous images
    gallery.innerHTML = "";

    /*
    =====================================
    IMAGE SUPPORT
    =====================================
    */

    if(image && image.trim() !== ""){

        // Supports future multiple-image implementation
        const images = image.split(",");

        images.forEach(function(imgName){

            imgName = imgName.trim();

            if(imgName !== ""){

                const img = document.createElement("img");

                img.src = "../uploads/news/" + imgName;

                img.className = "modal-image";

                gallery.appendChild(img);

            }

        });

    }

    modal.style.display = "flex";

}

/*
=====================================
CLOSE MODAL
=====================================
*/

function closeNews(){

    modal.style.display = "none";

}

/*
=====================================
CLICK X BUTTON
=====================================
*/

closeBtn.addEventListener("click", closeNews);

/*
=====================================
CLICK OUTSIDE MODAL
=====================================
*/

window.addEventListener("click",function(e){

    if(e.target === modal){

        closeNews();

    }

});

/*
=====================================
ESC KEY
=====================================
*/

document.addEventListener("keydown",function(e){

    if(e.key === "Escape"){

        closeNews();

    }

});

/*
=====================================
PREVENT IMAGE DRAGGING
=====================================
*/

document.addEventListener("dragstart",function(e){

    if(e.target.tagName === "IMG"){

        e.preventDefault();

    }

});

/*
=====================================
SMOOTH OPEN ANIMATION
=====================================
*/

modal.addEventListener("transitionend",function(){

    if(modal.style.display === "flex"){

        modal.scrollTop = 0;

    }

});