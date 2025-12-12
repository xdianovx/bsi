import path from "path";

const buildFolder = "./dist";
const srcFolder = ".";

const filePaths = {
	build: {
		js: `${buildFolder}/js/`,
		css: `${buildFolder}/css/`,
	},
	src: {
		js: `${srcFolder}/js/*.js`,
		scss: [`${srcFolder}/scss/main.scss`, `${srcFolder}/scss/pages/*.scss`],
	},
	watch: {
		js: `${srcFolder}/js/**/*.js`,
		scss: `${srcFolder}/scss/**/*.scss`,
	},
	buildFolder,
	srcFolder,
	projectDirName: path.basename(path.resolve()),
};

export { filePaths };
