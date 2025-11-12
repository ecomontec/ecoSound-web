import sys
import json
from statistics import mean


def merge_tags(tags, unknown_id, model, max_gap=0, keep_only_merged=True):
    tags = sorted(tags, key=lambda x: (int(x['species_id']), str(x['comments']), float(x['min_time'])))
    merged = []
    buffer = []

    def flush_buffer():
        if not buffer:
            return None
        if len(buffer) == 1:
            tag = buffer[0].copy()
            tag["_merged"] = False
            tag["confidence"] = round(float(tag["confidence"]), 4)
            tag["min_time"] = float(tag["min_time"])
            tag["max_time"] = float(tag["max_time"])
            tag["min_freq"] = float(tag["min_freq"])
            tag["max_freq"] = float(tag["max_freq"])
            return tag

        base = buffer[0].copy()
        base["min_time"] = min(float(t["min_time"]) for t in buffer)
        base["max_time"] = max(float(t["max_time"]) for t in buffer)
        base["min_freq"] = min(float(t["min_freq"]) for t in buffer)
        base["max_freq"] = max(float(t["max_freq"]) for t in buffer)
        confs = [float(t["confidence"]) for t in buffer]
        base["confidence"] = round(mean(confs), 4)
        comments = [t["comments"] for t in buffer]
        base["comments"] = (
                f"{comments[0]}, merged {len(buffer)} {model} tags with confidence scores: "
                + ", ".join(str(round(c, 4)) for c in confs)
        )
        base["_merged"] = True
        return base

    for tag in tags:
        if not buffer:
            buffer.append(tag)
            continue

        last = buffer[-1]

        if last["species_id"] == tag["species_id"] != unknown_id:
            if float(tag["min_time"]) - float(last["max_time"]) <= max_gap:
                buffer.append(tag)
                continue
        elif last["species_id"] == unknown_id and tag["species_id"] == unknown_id:
            if last["comments"] == tag["comments"] and float(tag["min_time"]) - float(last["max_time"]) <= max_gap:
                buffer.append(tag)
                continue

        merged_tag = flush_buffer()
        if merged_tag:
            merged.append(merged_tag)
        buffer = [tag]

    merged_tag = flush_buffer()
    if merged_tag:
        merged.append(merged_tag)

    if keep_only_merged:
        result = merged
    else:
        original_unmerged = [t for t in tags if not t.get("_merged", False)]
        merged_only = [t for t in merged if t.get("_merged", False)]
        result = original_unmerged + merged_only

    for t in result:
        t.pop("_merged", None)

    return result


json_file = sys.argv[1]
unknown_id = int(sys.argv[2])
model = sys.argv[3]
max_gap = float(sys.argv[4]) if len(sys.argv) > 4 else 0
keep_only_merged = bool(int(sys.argv[5])) if len(sys.argv) > 5 else False

with open(json_file, 'r', encoding='utf-8') as f:
    tags = json.load(f)

merged_tags = merge_tags(tags, unknown_id, model, max_gap, keep_only_merged)
print(json.dumps(merged_tags))
