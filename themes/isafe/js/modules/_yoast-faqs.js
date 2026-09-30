/**
 * Initialize Yoast SEO FAQ accordion functionality.
 */
export default function initYoastFaqs() {
	const faqQuestions = document.querySelectorAll('.schema-faq-question');

	faqQuestions.forEach(question => {
		question.addEventListener('click', function () {
			const answer = this.nextElementSibling;
			if (!answer) return;

			const isActive = answer.classList.contains('active');

			// Close all other answers and reset active state on questions
			document.querySelectorAll('.schema-faq-answer').forEach(ans => {
				ans.classList.remove('active');
				ans.style.maxHeight = '';
			});
			document.querySelectorAll('.schema-faq-question').forEach(q => {
				q.classList.remove('active');
			});

			// Toggle the clicked answer and its question. Set max-height to the
			// answer's actual content height (rather than a % or large fixed
			// value) so the CSS transition animates smoothly to exactly where
			// the content ends, instead of snapping or overshooting.
			if (!isActive) {
				answer.classList.add('active');
				this.classList.add('active');
				answer.style.maxHeight = `${answer.scrollHeight}px`;
			}
		});
	});
}
