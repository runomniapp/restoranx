// Tezgah Burger - QR Code Generator & High-Res PDF Table Tent Builder

class TezgahQRBuilder {
    constructor() {
        this.fgColorInput = document.getElementById('qrFgColor');
        this.bgColorInput = document.getElementById('qrBgColor');
        this.headerTextInput = document.getElementById('qrHeaderText');
        this.subheaderTextInput = document.getElementById('qrSubheaderText');
        this.tableNoteInput = document.getElementById('qrTableNote');
        this.footerTextInput = document.getElementById('qrFooterText');
        this.categorySelect = document.getElementById('qrCategorySelect');
        this.logoUploadInput = document.getElementById('qrLogoUpload');
        this.pdfExportBtn = document.getElementById('exportPdfBtn');

        this.previewHeader = document.getElementById('previewHeader');
        this.previewSubheader = document.getElementById('previewSubheader');
        this.previewTableNote = document.getElementById('previewTableNote');
        this.previewFooter = document.getElementById('previewFooter');
        this.qrCardBox = document.getElementById('qrTableTentCard');

        // DB'deki yol "assets/..." biçiminde saklanır; admin klasöründen çözümlemek için ../public/ ön eki gerekir.
        const storedLogo = document.getElementById('currentLogoUrl')?.value || 'assets/images/logo.jpg';
        this.currentLogoUrl = /^(https?:|data:|\.\.\/)/.test(storedLogo) ? storedLogo : '../public/' + storedLogo;

        this.init();
    }

    init() {
        if (!this.qrCardBox) return;

        // Attach Event Listeners
        [this.fgColorInput, this.bgColorInput, this.headerTextInput, this.subheaderTextInput, 
         this.tableNoteInput, this.footerTextInput, this.categorySelect].forEach(input => {
            if (input) {
                input.addEventListener('input', () => this.updatePreview());
            }
        });

        if (this.logoUploadInput) {
            this.logoUploadInput.addEventListener('change', (e) => this.handleLogoUpload(e));
        }

        if (this.pdfExportBtn) {
            this.pdfExportBtn.addEventListener('click', () => this.exportToPDF());
        }

        this.updatePreview();
    }

    handleLogoUpload(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                this.currentLogoUrl = event.target.result;
                const logoImgEl = document.getElementById('previewLogoImg');
                if (logoImgEl) logoImgEl.src = this.currentLogoUrl;
                this.updatePreview();
            };
            reader.readAsDataURL(file);
        }
    }

    getTargetURL() {
        const baseUrl = window.location.origin + window.location.pathname.replace('/admin/qr_builder.php', '/qr.php');
        const catId = this.categorySelect ? this.categorySelect.value : 'all';
        if (catId && catId !== 'all') {
            return `${baseUrl}?cat=${catId}`;
        }
        return baseUrl;
    }

    updatePreview() {
        // Update Text
        if (this.previewHeader && this.headerTextInput) this.previewHeader.textContent = this.headerTextInput.value;
        if (this.previewSubheader && this.subheaderTextInput) this.previewSubheader.textContent = this.subheaderTextInput.value;
        if (this.previewTableNote && this.tableNoteInput) this.previewTableNote.textContent = this.tableNoteInput.value;
        if (this.previewFooter && this.footerTextInput) this.previewFooter.textContent = this.footerTextInput.value;

        // Update Colors on Card
        const fg = this.fgColorInput ? this.fgColorInput.value : '#C87A4B';
        const bg = this.bgColorInput ? this.bgColorInput.value : '#121110';

        if (this.qrCardBox) {
            this.qrCardBox.style.backgroundColor = bg;
            this.qrCardBox.style.borderColor = fg;
            this.qrCardBox.style.color = (bg.toLowerCase() === '#ffffff' || bg.toLowerCase() === '#fff') ? '#121110' : '#F7F4F0';
        }

        // Generate QR Canvas
        this.renderQRCanvas(fg, bg);
    }

    renderQRCanvas(fgColor, bgColor) {
        const qrContainer = document.getElementById('qrCanvasWrapper');
        if (!qrContainer) return;
        qrContainer.innerHTML = '';

        const targetUrl = this.getTargetURL();
        
        // Use QRCode.js or fallback HTML5 SVG/Canvas QR Generator
        if (typeof QRCode !== 'undefined') {
            new QRCode(qrContainer, {
                text: targetUrl,
                width: 200,
                height: 200,
                colorDark: fgColor,
                colorLight: bgColor,
                correctLevel: QRCode.CorrectLevel.H
            });

            // Overlay Logo after render
            setTimeout(() => {
                const canvas = qrContainer.querySelector('canvas');
                if (canvas && this.currentLogoUrl) {
                    const ctx = canvas.getContext('2d');
                    const img = new Image();
                    img.crossOrigin = 'Anonymous';
                    img.src = this.currentLogoUrl;
                    img.onload = () => {
                        const logoSize = canvas.width * 0.24;
                        const x = (canvas.width - logoSize) / 2;
                        const y = (canvas.height - logoSize) / 2;
                        
                        // Draw background badge for logo
                        ctx.fillStyle = bgColor;
                        ctx.beginPath();
                        ctx.arc(canvas.width / 2, canvas.height / 2, logoSize / 2 + 4, 0, Math.PI * 2);
                        ctx.fill();

                        ctx.strokeStyle = fgColor;
                        ctx.lineWidth = 2;
                        ctx.stroke();

                        // Clip & draw logo image
                        ctx.save();
                        ctx.beginPath();
                        ctx.arc(canvas.width / 2, canvas.height / 2, logoSize / 2, 0, Math.PI * 2);
                        ctx.clip();
                        ctx.drawImage(img, x, y, logoSize, logoSize);
                        ctx.restore();
                    };
                }
            }, 100);
        } else {
            qrContainer.innerHTML = `<div style="padding:1rem; border:1px dashed ${fgColor}; color:${fgColor}; font-weight:bold;">[QR Kod: ${targetUrl}]</div>`;
        }
    }

    exportToPDF() {
        if (!this.qrCardBox) return;

        if (typeof html2canvas !== 'undefined' && typeof jspdf !== 'undefined') {
            const { jsPDF } = jspdf;
            const card = this.qrCardBox;

            html2canvas(card, {
                scale: 3,
                useCORS: true,
                backgroundColor: null
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a5'
                });

                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();

                pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth - 20, pdfHeight - 20);
                pdf.save('tezgah_burger_qr_masa_karti.pdf');
            });
        } else {
            window.print();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.tezgahQR = new TezgahQRBuilder();
});
