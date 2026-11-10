import argparse
from pathlib import Path

from ema_lightning import EMA


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input-file", required=True)
    parser.add_argument("--output-file", required=True)
    parser.add_argument("--sample-rate", type=int, default=48000)
    parser.add_argument("--speed", type=float, default=0.85)
    args = parser.parse_args()

    text = Path(args.input_file).read_text(encoding="utf-8").strip()
    if not text:
        raise ValueError("TTS metni boş olamaz.")

    output = Path(args.output_file)
    output.parent.mkdir(parents=True, exist_ok=True)
    EMA().say(
        text,
        path=str(output),
        sample_rate=args.sample_rate,
        speed=max(0.25, min(4.0, args.speed)),
    )


if __name__ == "__main__":
    main()
