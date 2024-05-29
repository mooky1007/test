class LabelResizer {
    constructor(el) {
        this.el = el;
        this.timer = null;

        this.observe();
        window.addEventListener('resize', this.debounce(() => this.render(), 100));
    }

    collectData() {
        const rows = [...this.el.querySelectorAll('.search_wrap_row')];

        const result = rows.reduce((acc, row, rowIdx, rowOrigin) => {
            const cols = [...row.querySelectorAll('.search_wrap_col')];

            cols.forEach((col, idx) => {
                const colspan = col.className.match(/col-(\d+)/)[1];
                const labelEl = col.querySelector('.label_cell');
                const colIdx = idx === 0 ? 0 : +cols[idx - 1].getAttribute('col-idx') + (+cols[idx - 1].className.match(/col-(\d+)/)[1] || 1);
                col.setAttribute('col-idx', colIdx);

                if (!acc[colIdx]) acc[colIdx] = [];
                acc[colIdx].push({ labelEl, colspan });
            });
            return acc;
        }, {});

        document.querySelectorAll('[col-idx]').forEach((el) => el.removeAttribute('col-idx'));
        return result;
    }

    observe() {
        // 동적으로 라벨내용이 변경되는 일이 없다면 해당 함수제거
        const observer = new MutationObserver(() => this.render());
        observer.observe(this.el, {
            childList: true,
            subtree: true,
            characterDataOldValue: true,
        });

        this.render();
    }

    debounce(fn, delay) {
        let timer = null;
        return (...args) => {
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }

    render() {
        Object.values(this.collectData()).forEach((labels) => {
            labels.forEach((labelObj) => (labelObj.labelEl.style.width = 'auto'));
            const maxWidth = Math.max(...labels.map((labelObj) => labelObj.labelEl.offsetWidth + 5));
            const minWidth = Math.min(...labels.map((labelObj) => labelObj.labelEl.closest('.search_wrap_col').offsetWidth / 2));

            labels.forEach((labelObj) => {
                labelObj.labelEl.style.width = `${maxWidth}px`;
                labelObj.labelEl.style.maxWidth = `${minWidth}px`;
            });
        });
    }
}
