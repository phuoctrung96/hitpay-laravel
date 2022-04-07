export default {
    methods: {
        isValidImageFile(fileName) {
            let allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;

            return allowedExtensions.exec(fileName);
        },

        isValidFile(fileName) {
            let notAllowedExtensions = /(\.php|\.exe|\.py|\.sh|\.sql)$/i;

            return !notAllowedExtensions.exec(fileName);
        }
    }
}
