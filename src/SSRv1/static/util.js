function debounce(f, wait)
{
	let timeout;
	return function () {
		let ctx = this, args = arguments;
		timeout && clearTimeout(timeout);
		timeout = setTimeout(() => {
			timeout = null;
			f.apply(ctx, args);
		}, wait);
	};
}