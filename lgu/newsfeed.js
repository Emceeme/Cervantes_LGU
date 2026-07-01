// =====================================
// LGU NEWSFEED JAVASCRIPT
// Municipality of Cervantes
// =====================================

// ==============================
// MODALS
// ==============================

const createModal = document.getElementById("createModal");
const editModal = document.getElementById("editModal");

// ==============================
// CREATE MODAL
// ==============================

function openCreate(){

    createModal.style.display = "flex";

}

function closeCreate(){

    createModal.style.display = "none";

    document.querySelector("#createModal form").reset();

    const preview = document.getElementById("createPreview");

    if(preview){

        preview.style.display = "none";

        preview.src = "";

    }

}

// ==============================
// EDIT MODAL
// ==============================

function openEdit(id,title,content){

    document.getElementById("edit_id").value = id;

    document.getElementById("edit_title").value = title;

    document.getElementById("edit_content").value = content;

    const preview = document.getElementById("editPreview");

    if(preview){

        preview.style.display = "none";

        preview.src = "";

    }

    editModal.style.display = "flex";

}

function closeEdit(){

    editModal.style.display = "none";

}

// ==============================
// CLOSE MODAL OUTSIDE
// ==============================

window.onclick = function(e){

    if(e.target == createModal){

        closeCreate();

    }

    if(e.target == editModal){

        closeEdit();

    }

}

// ==============================
// ESC KEY
// ==============================

document.addEventListener("keydown",function(e){

    if(e.key === "Escape"){

        closeCreate();

        closeEdit();

    }

});

// ==============================
// CREATE IMAGE PREVIEW
// ==============================

const createImage = document.querySelector(
'#createModal input[type="file"]'
);

if(createImage){

createImage.addEventListener("change",function(){

    const preview =
    document.getElementById("createPreview");

    if(!preview) return;

    if(this.files.length>0){

        preview.src =
        URL.createObjectURL(this.files[0]);

        preview.style.display="block";

    }

});

}

// ==============================
// EDIT IMAGE PREVIEW
// ==============================

const editImage = document.querySelector(
'#editModal input[type="file"]'
);

if(editImage){

editImage.addEventListener("change",function(){

    const preview =
    document.getElementById("editPreview");

    if(!preview) return;

    if(this.files.length>0){

        preview.src =
        URL.createObjectURL(this.files[0]);

        preview.style.display="block";

    }

});

}

// ==============================
// DELETE CONFIRMATION
// ==============================

const deleteButtons =
document.querySelectorAll(".delete-btn");

deleteButtons.forEach(function(button){

button.addEventListener("click",function(e){

    if(!confirm("Are you sure you want to delete this news post?")){

        e.preventDefault();

    }

});

});

// ==============================
// PREVENT IMAGE DRAGGING
// ==============================

document.addEventListener("dragstart",function(e){

    if(e.target.tagName==="IMG"){

        e.preventDefault();

    }

});

// ==============================
// SMOOTH SCROLL TO TOP
// ==============================

window.onload=function(){

    window.scrollTo({

        top:0,

        behavior:"smooth"

    });

}