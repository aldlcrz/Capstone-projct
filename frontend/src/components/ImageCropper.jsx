"use client";

import React, {
  useState,
  useEffect,
  useRef,
  useCallback,
  useImperativeHandle,
  forwardRef,
} from "react";
import { ImagePlus, Loader2 } from "lucide-react";

const ImageCropper = forwardRef(({ initialImage = "", onSave }, ref) => {
  const [image, setImage] = useState(initialImage);
  const [crop, setCrop] = useState({ x: 0, y: 0, w: 100, h: 100 });
  const [zoom, setZoom] = useState(1);
  const [offset, setOffset] = useState({ x: 0, y: 0 });
  const [interaction, setInteraction] = useState(null);
  const [isSaving, setIsSaving] = useState(false);

  const containerRef = useRef(null);
  const fileInputRef = useRef(null);
  const imageRef = useRef(null);

  const MIN_SIZE = 10;

  useImperativeHandle(ref, () => ({
    save: async () => {
      if (!image) return null;
      return await handleSave();
    },
    hasImage: !!image,
    isNewImage: !!image && image.startsWith("data:"),
  }));

  useEffect(() => {
    if (initialImage) setImage(initialImage);
  }, [initialImage]);

  const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      setImage(event.target.result);
      setCrop({ x: 0, y: 0, w: 100, h: 100 });
      setZoom(1);
      setOffset({ x: 0, y: 0 });
    };
    reader.readAsDataURL(file);
  };

  const startInteraction = (e, type, handle = null) => {
    e.preventDefault();

    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;

    setInteraction({
      type,
      handle,
      startX: clientX,
      startY: clientY,
      startCrop: { ...crop },
      startOffset: { ...offset },
    });
  };

  const handleMouseMove = useCallback(
    (e) => {
      if (!interaction || !containerRef.current) return;

      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const clientY = e.touches ? e.touches[0].clientY : e.clientY;

      const rect = containerRef.current.getBoundingClientRect();
      const dx = ((clientX - interaction.startX) / rect.width) * 100;
      const dy = ((clientY - interaction.startY) / rect.height) * 100;

      let newCrop = { ...interaction.startCrop };

      if (interaction.type === "move") {
        newCrop.x = Math.max(0, Math.min(100 - newCrop.w, newCrop.x + dx));
        newCrop.y = Math.max(0, Math.min(100 - newCrop.h, newCrop.y + dy));
      }

      if (interaction.type === "resize") {
        const h = interaction.handle;

        if (h.includes("e")) newCrop.w = Math.max(MIN_SIZE, newCrop.w + dx);
        if (h.includes("s")) newCrop.h = Math.max(MIN_SIZE, newCrop.h + dy);

        if (h.includes("w")) {
          newCrop.x += dx;
          newCrop.w = Math.max(MIN_SIZE, newCrop.w - dx);
        }

        if (h.includes("n")) {
          newCrop.y += dy;
          newCrop.h = Math.max(MIN_SIZE, newCrop.h - dy);
        }

        newCrop.x = Math.max(0, Math.min(100 - newCrop.w, newCrop.x));
        newCrop.y = Math.max(0, Math.min(100 - newCrop.h, newCrop.y));
      }

      if (interaction.type === "pan") {
        setOffset({
          x: interaction.startOffset.x + (clientX - interaction.startX),
          y: interaction.startOffset.y + (clientY - interaction.startY),
        });
      }

      setCrop(newCrop);
    },
    [interaction]
  );

  const stopInteraction = useCallback(() => setInteraction(null), []);

  useEffect(() => {
    if (interaction) {
      window.addEventListener("mousemove", handleMouseMove);
      window.addEventListener("mouseup", stopInteraction);
      window.addEventListener("touchmove", handleMouseMove);
      window.addEventListener("touchend", stopInteraction);
    }
    return () => {
      window.removeEventListener("mousemove", handleMouseMove);
      window.removeEventListener("mouseup", stopInteraction);
      window.removeEventListener("touchmove", handleMouseMove);
      window.removeEventListener("touchend", stopInteraction);
    };
  }, [interaction, handleMouseMove, stopInteraction]);

  const getCroppedBlob = async () => {
    const img = imageRef.current;
    const container = containerRef.current;
    if (!img || !container) return null;

    const canvas = document.createElement("canvas");

    const rect = container.getBoundingClientRect();
    const imgRect = img.getBoundingClientRect();

    const scaleX = img.naturalWidth / imgRect.width;
    const scaleY = img.naturalHeight / imgRect.height;

    const cropPx = {
      x: (crop.x / 100) * rect.width,
      y: (crop.y / 100) * rect.height,
      w: (crop.w / 100) * rect.width,
      h: (crop.h / 100) * rect.height,
    };

    const sx = (cropPx.x - (imgRect.left - rect.left)) * scaleX;
    const sy = (cropPx.y - (imgRect.top - rect.top)) * scaleY;
    const sw = cropPx.w * scaleX;
    const sh = cropPx.h * scaleY;

    canvas.width = sw;
    canvas.height = sh;

    const ctx = canvas.getContext("2d");

    ctx.drawImage(
      img,
      sx,
      sy,
      sw,
      sh,
      0,
      0,
      sw,
      sh
    );

    return new Promise((resolve) => {
      canvas.toBlob((blob) => resolve(blob), "image/jpeg", 0.92);
    });
  };

  const handleSave = async () => {
    setIsSaving(true);
    try {
      const blob = await getCroppedBlob();
      if (!blob) return null;
      return await onSave({ croppedBlob: blob });
    } finally {
      setIsSaving(false);
    }
  };

  const handleReset = () => {
    if (initialImage) setImage(initialImage);
    setCrop({ x: 0, y: 0, w: 100, h: 100 });
    setZoom(1);
    setOffset({ x: 0, y: 0 });
  };

  return (
    <div className="space-y-6">
      <div
        ref={containerRef}
        className="relative w-full h-[400px] bg-black border rounded-xl overflow-hidden"
      >
        {!image ? (
          <div
            onClick={() => fileInputRef.current?.click()}
            className="absolute inset-0 flex flex-col items-center justify-center text-white cursor-pointer"
          >
            <ImagePlus />
            <p className="text-xs mt-2">Upload Image</p>
          </div>
        ) : (
          <>
            <img
              ref={imageRef}
              src={image}
              crossOrigin="anonymous"
              onMouseDown={(e) => startInteraction(e, "pan")}
              onTouchStart={(e) => startInteraction(e, "pan")}
              className="absolute select-none cursor-grab"
              style={{
                top: "50%",
                left: "50%",
                transform: `
                  translate(-50%, -50%)
                  translate(${offset.x}px, ${offset.y}px)
                  scale(${zoom})
                `,
                transformOrigin: "center",
                maxWidth: "100%",
                maxHeight: "100%",
                objectFit: "contain",
              }}
            />

            <div
              style={{
                left: `${crop.x}%`,
                top: `${crop.y}%`,
                width: `${crop.w}%`,
                height: `${crop.h}%`,
                boxShadow: "0 0 0 4000px rgba(0,0,0,0.6)",
              }}
              onMouseDown={(e) => startInteraction(e, "move")}
              onTouchStart={(e) => startInteraction(e, "move")}
            >
              {[].map((h) => (
                <div
                  key={h}
                  onMouseDown={(e) => startInteraction(e, "resize", h)}
                  onTouchStart={(e) => startInteraction(e, "resize", h)}
                  className="absolute w-4 h-4 bg-white border border-black rounded-full"
                  style={{
                    top: h.includes("n") ? -6 : h === "s" ? "100%" : "50%",
                    left: h.includes("w") ? -6 : h === "e" ? "100%" : "50%",
                    transform: "translate(-50%, -50%)",
                    cursor: `${h}-resize`,
                  }}
                />
              ))}
            </div>
          </>
        )}
      </div>

      {image && (
        <input
          type="range"
          min="1"
          max="3"
          step="0.01"
          value={zoom}
          onChange={(e) => setZoom(Number(e.target.value))}
          className="w-full"
        />
      )}

      <div className="flex gap-3">
        <input type="file" ref={fileInputRef} onChange={handleFileChange} hidden />

        <button
          onClick={() => fileInputRef.current?.click()}
          className="px-4 py-2 border rounded-lg text-sm font-medium transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--bark)] hover:text-white hover:border-[var(--bark)] hover:shadow-md"
        >
          Upload
        </button>

        <button
          onClick={handleReset}
          className="px-4 py-2 border rounded-lg text-sm font-medium transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--bark)] hover:text-white hover:border-[var(--bark)] hover:shadow-md"
        >
          Reset
        </button>

        {isSaving && (
          <div className="flex items-center gap-2 text-xs font-bold text-[var(--rust)]">
            <Loader2 className="w-4 h-4 animate-spin" /> Cropping...
          </div>
        )}
      </div>
    </div>
  );
});

ImageCropper.displayName = "ImageCropper";
export default ImageCropper;