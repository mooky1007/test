document.querySelectorAll('.search_wrap')
.forEach((searchWrap) => {
    const pre = document.createElement('pre');
    const code = document.createElement('code');
    code.classList.add('hljs');
    code.classList.add('xml');
    code.textContent = searchWrap.outerHTML;

    pre.appendChild(code);
    searchWrap.parentNode.insertBefore(pre, searchWrap.nextSibling);
});

hljs.highlightAll();
