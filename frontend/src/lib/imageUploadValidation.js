export const IMAGE_UPLOAD_RULES = {
  acceptedMimeTypes: ["image/jpeg", "image/png", "image/jfif"],
  acceptedExtensions: [".jpg", ".jpeg", ".jfif", ".png"],
  maxFileSizeBytes: 5 * 1024 * 1024,
  maxFilesPerRequest: 5,
  minWidth: 300,
  minHeight: 300,
  maxWidth: 5000,
  maxHeight: 5000,
};

const getFileExtension = (name = "") => {
  const lastDot = String(name).lastIndexOf(".");
  return lastDot >= 0 ? String(name).slice(lastDot).toLowerCase() : "";
};

const loadImageDimensions = (file) =>
  new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    image.onload = () => {
      const dimensions = {
        width: image.naturalWidth,
        height: image.naturalHeight,
      };
      URL.revokeObjectURL(objectUrl);
      resolve(dimensions);
    };

    image.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      reject(new Error("Selected file could not be read as a valid image."));
    };

    image.src = objectUrl;
  });
export async function validateImageFile(file, label = "Image") {
  if (!file) {
    return `${label} is missing.`;
  }

  if (!file.type || !file.type.startsWith("image/")) {
    return `${label} must be a valid image file.`;
  }

  if (file.size > IMAGE_UPLOAD_RULES.maxFileSizeBytes) {
    return `${label} must be 5MB or smaller.`;
  }

  return null;
}
export async function validateImageFiles(files, labelPrefix = "Image") {
  const list = Array.from(files || []);

  if (list.length > IMAGE_UPLOAD_RULES.maxFilesPerRequest) {
    return {
      validFiles: [],
      errors: [`You can upload up to ${IMAGE_UPLOAD_RULES.maxFilesPerRequest} images at a time.`],
    };
  }

  const results = await Promise.all(
    list.map(async (file, index) => ({
      file,
      error: await validateImageFile(file, `${labelPrefix} ${index + 1}`),
    }))
  );

  return {
    validFiles: results.filter((entry) => !entry.error).map((entry) => entry.file),
    errors: results.filter((entry) => entry.error).map((entry) => entry.error),
  };
}
