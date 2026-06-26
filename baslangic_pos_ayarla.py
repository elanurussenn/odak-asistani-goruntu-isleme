from flask import Flask, request, jsonify
from flask_cors import CORS
from modes.yogun_mode import run_yogun_mode
from modes.baslangic_pos_ayarla import baslangic_pos_ayarla
from modes.yks_mode import yks_state_reset
from modes.yks_mode import run_yks_mode
from modes.yogun_mode import yogun_state_reset
from modes.yogun_mode import run_yogun_mode
#request ile kullanıcıdan gelen istekleri alırım


app = Flask(__name__)
CORS(app)


@app.route("/")
def home():
    return "Flask çalışıyor"  #test amaçlı olarak yazdım sonra silinebilir

@app.route("/yks_reset", methods=["POST"])
def yks_reset():
    try:
        yks_state_reset()
        print("YKS RESET CALISTI")
        return jsonify({
            "sonuc": "ok"
        })
    except Exception as e:
        return jsonify({
            "sonuc": "Hata",
            "error": str(e)
        })
@app.route("/yogun_reset", methods=["POST"])
def yogun_reset():
    try:
        yogun_state_reset()
        print("YOGUN RESET CALISTI")
        return jsonify({
            "sonuc": "ok"
        })
    except Exception as e:
        return jsonify({
            "sonuc": "Hata",
            "error": str(e)
        })


#buraya post ıle goruntu ve mod yollandı,gelen istegi uygun analız dosyasına ıletıyorum
@app.route("/analiz", methods=["POST"])
def analiz():
    try:
        data = request.json
        image = data.get("image")
        mod = data.get("mod")

        if not image:
            return jsonify({"sonuc": "Görüntü gelmedi"})

        if mod == "yks":
            sonuc = run_yks_mode(image)
        elif mod == "yogun":
            sonuc = run_yogun_mode(image)
        else:
            return jsonify({"sonuc": "Geçersiz mod"})

        return jsonify(sonuc)

    except Exception as e:
        return jsonify({
            "sonuc": "Hata",
            "error": str(e)
        })


@app.route("/baslangic_pos_ayarla", methods=["POST"])
def baslangic_pozisyon_ayarla():
    try:
        data = request.json
        image = data.get("image")

        if not image:
            return jsonify({
                "sonuc": "Goruntu gelmedi"
            })

        sonuc = baslangic_pos_ayarla(image)
        return jsonify(sonuc)

    except Exception as e:
        return jsonify({
            "sonuc": "Hata",
            "error": str(e)
        })

if __name__ == "__main__":
    app.run(debug=True)