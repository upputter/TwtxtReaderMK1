/*
 * Modal
 *
 * Pico.css - https://picocss.com
 * Copyright 2019-2024 - Licensed under MIT
 */

// Config
const isOpenClass = "modal-is-open";
const openingClass = "modal-is-opening";
const closingClass = "modal-is-closing";
const scrollbarWidthCssVar = "--pico-scrollbar-width";
const animationDuration = 0; // ms
let visibleModal = null;



// Toggle modal
const toggleModal = (event) => {
  event.preventDefault();  
  const modal = document.getElementById(event.currentTarget.dataset.target);
  contentTarget = document.getElementById(event.currentTarget.dataset.contentTarget);
  headerTarget = document.getElementById(event.currentTarget.dataset.headerTarget);
  content = event.currentTarget.dataset.content;
  headerContent = event.currentTarget.dataset.headerContent;

  if (!modal) return;  
  modal && (modal.open ? closeModal(modal) : openModal(
    modal, 
    contentTarget, 
    content,
    headerTarget,
    headerContent
  ));
};

// Open modal
const openModal = (modal, contentTarget, content, headerTarget, headerContent) => {
  const { documentElement: html } = document;
  const scrollbarWidth = getScrollbarWidth();
  if (scrollbarWidth) {
    html.style.setProperty(scrollbarWidthCssVar, `${scrollbarWidth}px`);
  }
  html.classList.add(isOpenClass, openingClass);
  setTimeout(() => {
    visibleModal = modal;
    html.classList.remove(openingClass);
  }, animationDuration); 
  if (content && contentTarget) contentTarget.value = content;
  if (headerContent && headerTarget) headerTarget.textContent = headerContent;  
  modal.showModal();
  if (mdEditor) {
    // reset the editor content    
    mdEditor.setContent('');
    mdEditor.paste(content);
  };  
};

// Close modal
const closeModal = (modal) => {
  visibleModal = null;
  const { documentElement: html } = document;
  html.classList.add(closingClass);
  setTimeout(() => {
    html.classList.remove(closingClass, isOpenClass);
    html.style.removeProperty(scrollbarWidthCssVar);
    // hide emoji picker
    document.getElementById('emojiTrigger').click();
    document.getElementById('lc-emoji-picker').classList.remove('lcep-shown');
    document.getElementById('emojiCMDButton').parentElement.style="";
    
    modal.close();
  }, animationDuration);
};

// Close with a click outside
document.addEventListener("click", (event) => {
  return;
  if (visibleModal === null) return;
  const modalContent = visibleModal.querySelector("article");
  const isClickInside = modalContent.contains(event.target);
  !isClickInside && closeModal(visibleModal);
});

// Close with Esc key
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && visibleModal) {
    closeModal(visibleModal);
  }
});

// Get scrollbar width
const getScrollbarWidth = () => {
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  return scrollbarWidth;
};

// Is scrollbar visible
const isScrollbarVisible = () => {
  return document.body.scrollHeight > screen.height;
};