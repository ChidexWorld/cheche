// Language switching functionality
let currentLanguage = localStorage.getItem('language') || 'en';

const languageNames = {
    'en': 'English',
    'ig': 'Igbo'
};

async function translateText(text, targetLang) {
    if (targetLang === 'en') return text;
    try {
        const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=${targetLang}&dt=t&q=${encodeURIComponent(text)}`;
        const response = await fetch(url);
        const data = await response.json();
        return data[0][0][0];
    } catch (error) {
        console.error('Translation error:', error);
        return text;
    }
}

async function translatePage(targetLang) {
    // Translate elements with data-translate attributes
    const elements = document.querySelectorAll('[data-translate]');

    for (const element of elements) {
        if (element.dataset.originalText === undefined) {
            element.dataset.originalText = element.textContent.trim();
        }

        const originalText = element.dataset.originalText;
        if (originalText) {
            if (targetLang === 'en') {
                element.textContent = originalText;
            } else {
                const translatedText = await translateText(originalText, targetLang);
                element.textContent = translatedText;
            }
        }
    }

    // Also translate buttons and other UI elements automatically
    await translateAllButtons(targetLang);

    // Translate ALL other text content automatically
    await translateAllContent(targetLang);

    // Also translate dynamic content that doesn't have data-translate attributes
    await translateDynamicContent(targetLang);
}

async function translateAllButtons(targetLang) {
    // Translate all buttons
    const buttons = document.querySelectorAll('button:not(.language-toggle)');
    for (const button of buttons) {
        if (button.closest('.language-dropdown')) continue;

        const text = button.textContent.trim();
        if (text && text.length > 1) {
            if (!button.dataset.originalText) {
                button.dataset.originalText = text;
            }

            if (targetLang === 'en') {
                button.textContent = button.dataset.originalText;
            } else {
                try {
                    const translatedText = await translateText(text, targetLang);
                    button.textContent = translatedText;
                } catch (error) {
                    console.warn('Button translation failed:', text);
                }
            }
        }
    }

    // Translate all links that look like buttons
    const buttonLinks = document.querySelectorAll('a.btn-primary, a.btn-secondary, a.btn-outline, a.btn-danger');
    for (const link of buttonLinks) {
        if (link.closest('.language-dropdown')) continue;

        const text = link.textContent.trim();
        if (text && text.length > 1) {
            if (!link.dataset.originalText) {
                link.dataset.originalText = text;
            }

            if (targetLang === 'en') {
                link.textContent = link.dataset.originalText;
            } else {
                try {
                    const translatedText = await translateText(text, targetLang);
                    link.textContent = translatedText;
                } catch (error) {
                    console.warn('Button link translation failed:', text);
                }
            }
        }
    }

    // Translate input placeholders
    const inputs = document.querySelectorAll('input[placeholder]');
    for (const input of inputs) {
        if (!input.dataset.originalPlaceholder) {
            input.dataset.originalPlaceholder = input.placeholder;
        }

        if (targetLang === 'en') {
            input.placeholder = input.dataset.originalPlaceholder;
        } else {
            try {
                const translatedPlaceholder = await translateText(input.dataset.originalPlaceholder, targetLang);
                input.placeholder = translatedPlaceholder;
            } catch (error) {
                console.warn('Placeholder translation failed:', input.placeholder);
            }
        }
    }
}

async function translateAllContent(targetLang) {
    // Get all elements that might contain text
    const textElements = document.querySelectorAll('h1:not([data-translate]), h2:not([data-translate]), h3:not([data-translate]), h4:not([data-translate]), h5:not([data-translate]), h6:not([data-translate]), p:not([data-translate]), span:not([data-translate]), div:not([data-translate]), li:not([data-translate]), td:not([data-translate]), th:not([data-translate]), label:not([data-translate]), strong:not([data-translate]), em:not([data-translate]), small:not([data-translate])');

    for (const element of textElements) {
        // Skip if element is part of language dropdown or script/style
        if (element.closest('.language-dropdown') ||
            element.closest('script') ||
            element.closest('style') ||
            element.id === 'currentLang' ||
            element.classList.contains('language-toggle')) {
            continue;
        }

        // Only translate elements that have direct text content (not just child elements)
        const directText = Array.from(element.childNodes)
            .filter(node => node.nodeType === Node.TEXT_NODE)
            .map(node => node.textContent.trim())
            .join(' ')
            .trim();

        if (directText && directText.length > 1) {
            // Skip numbers, brand name, dates, emails, URLs
            if (/^\d+$/.test(directText) ||
                directText === 'Cheche' ||
                /^[\d\s\-\.:\/]+$/.test(directText) ||
                directText.includes('@') ||
                directText.startsWith('http')) {
                continue;
            }

            if (!element.dataset.originalText) {
                element.dataset.originalText = directText;
            }

            if (targetLang === 'en') {
                // Restore original text for text nodes
                Array.from(element.childNodes).forEach(node => {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                        const originalPart = element.dataset.originalText;
                        if (originalPart) {
                            node.textContent = originalPart;
                        }
                    }
                });
            } else {
                try {
                    const translatedText = await translateText(directText, targetLang);
                    // Update text nodes
                    Array.from(element.childNodes).forEach(node => {
                        if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                            node.textContent = translatedText;
                        }
                    });
                } catch (error) {
                    console.warn('Translation failed for:', directText);
                }
            }
        }
        // Handle elements with only text content (no child elements)
        else if (!element.children.length && element.textContent.trim()) {
            const text = element.textContent.trim();

            if (text.length > 1 &&
                !/^\d+$/.test(text) &&
                text !== 'Cheche' &&
                !/^[\d\s\-\.:\/]+$/.test(text) &&
                !text.includes('@') &&
                !text.startsWith('http')) {

                if (!element.dataset.originalText) {
                    element.dataset.originalText = text;
                }

                if (targetLang === 'en') {
                    element.textContent = element.dataset.originalText;
                } else {
                    try {
                        const translatedText = await translateText(text, targetLang);
                        element.textContent = translatedText;
                    } catch (error) {
                        console.warn('Translation failed for:', text);
                    }
                }
            }
        }
    }
}

async function translateDynamicContent(targetLang) {

    // Translate course cards
    const courseCards = document.querySelectorAll('.course-card');
    for (const card of courseCards) {
        // Translate course titles
        const title = card.querySelector('h3');
        if (title && !title.hasAttribute('data-translate')) {
            const originalText = title.dataset.originalText || title.textContent;
            if (!title.dataset.originalText) {
                title.dataset.originalText = originalText;
            }
            if (targetLang !== 'en') {
                const translatedText = await translateText(originalText, targetLang);
                title.textContent = translatedText;
            } else {
                title.textContent = originalText;
            }
        }

        // Translate course descriptions
        const description = card.querySelector('p');
        if (description && !description.hasAttribute('data-translate')) {
            const originalText = description.dataset.originalText || description.textContent;
            if (!description.dataset.originalText) {
                description.dataset.originalText = originalText;
            }
            if (targetLang !== 'en') {
                const translatedText = await translateText(originalText, targetLang);
                description.textContent = translatedText;
            } else {
                description.textContent = originalText;
            }
        }

        // Translate "By" text in course meta
        const metaSpans = card.querySelectorAll('.course-meta span');
        metaSpans.forEach(async span => {
            if (span.textContent.startsWith('By ')) {
                const instructorName = span.textContent.replace('By ', '');
                const byText = span.dataset.originalByText || 'By';
                if (!span.dataset.originalByText) {
                    span.dataset.originalByText = byText;
                    span.dataset.originalInstructorName = instructorName;
                }
                if (targetLang !== 'en') {
                    const translatedBy = await translateText(byText, targetLang);
                    span.textContent = `${translatedBy} ${instructorName}`;
                } else {
                    span.textContent = `${byText} ${instructorName}`;
                }
            } else if (span.textContent.includes(' videos')) {
                const videoCount = span.textContent.replace(' videos', '');
                const videosText = span.dataset.originalVideosText || 'videos';
                if (!span.dataset.originalVideosText) {
                    span.dataset.originalVideosText = videosText;
                    span.dataset.originalVideoCount = videoCount;
                }
                if (targetLang !== 'en') {
                    const translatedVideos = await translateText(videosText, targetLang);
                    span.textContent = `${videoCount} ${translatedVideos}`;
                } else {
                    span.textContent = `${videoCount} ${videosText}`;
                }
            } else if (span.textContent.includes(' students')) {
                const studentCount = span.textContent.replace(' students', '');
                const studentsText = span.dataset.originalStudentsText || 'students';
                if (!span.dataset.originalStudentsText) {
                    span.dataset.originalStudentsText = studentsText;
                    span.dataset.originalStudentCount = studentCount;
                }
                if (targetLang !== 'en') {
                    const translatedStudents = await translateText(studentsText, targetLang);
                    span.textContent = `${studentCount} ${translatedStudents}`;
                } else {
                    span.textContent = `${studentCount} ${studentsText}`;
                }
            }
        });
    }
}

function toggleDropdown() {
    const dropdown = document.getElementById('languageDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

async function changeLanguage(langCode) {
    currentLanguage = langCode;
    const currentLangSpan = document.getElementById('currentLang');
    if (currentLangSpan) {
        currentLangSpan.textContent = languageNames[langCode];
    }

    await translatePage(langCode);
    localStorage.setItem('language', currentLanguage);

    // Close dropdown
    const dropdown = document.getElementById('languageDropdown');
    if (dropdown) {
        dropdown.classList.remove('show');
    }
}

// Close dropdown when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('.language-toggle') && !event.target.matches('#currentLang')) {
        const dropdown = document.getElementById('languageDropdown');
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
}

// Initialize language on page load
document.addEventListener('DOMContentLoaded', async function() {
    const currentLangSpan = document.getElementById('currentLang');
    if (currentLangSpan) {
        currentLangSpan.textContent = languageNames[currentLanguage];
    }

    if (currentLanguage !== 'en') {
        await translatePage(currentLanguage);
    }
});