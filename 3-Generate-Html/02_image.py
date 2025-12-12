# 02_image.py - Version améliorée
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import pandas as pd
import os
import re
import cloudinary
import cloudinary.uploader
from dotenv import load_dotenv
from io import BytesIO

# Configuration
load_dotenv()

def configurer_cloudinary():
    """Configure Cloudinary et vérifie si les clés sont présentes."""
    cloud_name = os.getenv('CLOUDINARY_CLOUD_NAME')
    api_key = os.getenv('CLOUDINARY_API_KEY')
    api_secret = os.getenv('CLOUDINARY_API_SECRET')
    
    if cloud_name and api_key and api_secret:
        cloudinary.config(
            cloud_name=cloud_name,
            api_key=api_key,
            api_secret=api_secret
        )
        print("✅ Cloudinary configuré (Mode Online)")
        return True
    else:
        print("⚠️ Pas de clés Cloudinary trouvées (Mode Offline/Local)")
        return False

def creer_degrade(couleur1, couleur2, taille):
    """Crée un fond avec dégradé simple."""
    # Créer une image avec dégradé diagonal
    img = Image.new('RGB', taille, color=couleur1)
    draw = ImageDraw.Draw(img)
    
    # Dessiner un rectangle avec la deuxième couleur pour simuler un dégradé
    # Pour un vrai dégradé, on pourrait utiliser Image.linear_gradient mais restons simple
    for i in range(taille[0]):
        ratio = i / taille[0]
        r = int(couleur1[0] * (1 - ratio) + couleur2[0] * ratio)
        g = int(couleur1[1] * (1 - ratio) + couleur2[1] * ratio)
        b = int(couleur1[2] * (1 - ratio) + couleur2[2] * ratio)
        draw.line([(i, 0), (i, taille[1])], fill=(r, g, b))
    
    return img

def generer_logo(nom, taille=(180, 180)):
    """Génère un logo simple et élégant avec dégradé."""
    # Palettes de couleurs professionnelles
    palettes = [
        [('#2E86AB', '#4AA8D8'),  # Bleu
         ('#36676a', '#5A9B9E')], # Vert bleuté
        [('#A23B72', '#D45A9A'),  # Rose/violet
         ('#C73E1D', '#F05C3C')], # Orange/rouge
        [('#3B1F2B', '#5A3A4A'),  # Bordeaux
         ('#2d5aa0', '#4A7BC8')], # Bleu marine
        [('#228B22', '#3CB371'),  # Vert forêt
         ('#8B4513', '#CD853F')]  # Marron
    ]
    
    idx_palette = hash(nom) % len(palettes)
    couleur1_hex, couleur2_hex = palettes[idx_palette][0]
    
    # Convertir hex en RGB
    def hex_to_rgb(hex_color):
        hex_color = hex_color.lstrip('#')
        return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))
    
    couleur1 = hex_to_rgb(couleur1_hex)
    couleur2 = hex_to_rgb(couleur2_hex)
    
    # Créer le fond avec dégradé
    img = creer_degrade(couleur1, couleur2, taille)
    draw = ImageDraw.Draw(img)
    
    # Initiales (2 premières lettres des 2 premiers mots si possible)
    mots = nom.split()
    if len(mots) >= 2:
        initiales = f"{mots[0][0]}{mots[1][0]}".upper()
    else:
        initiales = nom[:2].upper() if len(nom) >= 2 else nom[0].upper()
    
    # Police élégante (essayer plusieurs polices)
    try:
        font = ImageFont.truetype("arialbd.ttf", 80)  # Un peu plus grand
    except:
        try:
            font = ImageFont.truetype("arial.ttf", 80)
        except:
            font = ImageFont.load_default()
    
    # Centrage précis
    bbox = draw.textbbox((0, 0), initiales, font=font)
    w, h = bbox[2] - bbox[0], bbox[3] - bbox[1]
    x, y = (taille[0] - w) // 2, (taille[1] - h) // 2 - 5  # Ajustement vertical
    
    # Texte avec ombre légère pour la profondeur
    draw.text((x+1, y+1), initiales, fill='rgba(0,0,0,30)', font=font)
    draw.text((x, y), initiales, fill='white', font=font)
    
    # Ajouter un contour subtil
    draw.rectangle([5, 5, taille[0]-6, taille[1]-6], outline='rgba(255,255,255,50)', width=1)
    
    return img

def generer_banniere(nom, taille=(1000, 250)):
    """Génère une bannière simple, épurée avec traits décoratifs."""
    # Réduire la taille pour économiser de la mémoire
    # Palette de couleurs sobres
    palettes = [
        ('#2E86AB', '#4AA8D8'),  # Bleu
        ('#36676a', '#5A9B9E'),  # Vert bleuté
        ('#3B1F2B', '#5A3A4A'),  # Bordeaux
        ('#2d5aa0', '#4A7BC8'),  # Bleu marine
        ('#555555', '#777777')   # Gris
    ]
    
    idx_couleur = hash(nom) % len(palettes)
    couleur1_hex, couleur2_hex = palettes[idx_couleur]
    
    def hex_to_rgb(hex_color):
        hex_color = hex_color.lstrip('#')
        return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))
    
    couleur1 = hex_to_rgb(couleur1_hex)
    couleur2 = hex_to_rgb(couleur2_hex)
    
    # Créer le fond avec dégradé
    img = creer_degrade(couleur1, couleur2, taille)
    draw = ImageDraw.Draw(img, 'RGBA')  # Mode RGBA pour la transparence
    
    # Traits décoratifs très fins et discrets
    # Ligne en haut à gauche
    draw.line([(10, 10), (80, 10)], fill='rgba(255,255,255,40)', width=1)
    draw.line([(10, 10), (10, 30)], fill='rgba(255,255,255,40)', width=1)
    
    # Ligne en bas à droite
    draw.line([(taille[0]-80, taille[1]-10), (taille[0]-10, taille[1]-10)], 
              fill='rgba(255,255,255,40)', width=1)
    draw.line([(taille[0]-10, taille[1]-30), (taille[0]-10, taille[1]-10)], 
              fill='rgba(255,255,255,40)', width=1)
    
    # Texte principal seulement (pas de sous-titre)
    try:
        # Police plus fine et élégante
        font = ImageFont.truetype("arial.ttf", 48)  # Taille réduite
    except:
        try:
            font = ImageFont.truetype("arialbd.ttf", 48)
        except:
            font = ImageFont.load_default()
    
    # Afficher le nom
    # On essaye de garder le nom complet autant que possible
    nom_display = nom
    if len(nom) > 30:
        nom_display = nom[:27] + "..."
        
    bbox = draw.textbbox((0, 0), nom_display, font=font)
    w, h = bbox[2] - bbox[0], bbox[3] - bbox[1]
    x, y = (taille[0] - w) // 2, (taille[1] - h) // 2
    
    # Ombre légère pour le texte
    draw.text((x+2, y+2), nom_display, fill='rgba(0,0,0,30)', font=font)
    # Texte principal
    draw.text((x, y), nom_display, fill='white', font=font)
    
    return img

def optimiser_taille_image(image, qualite=85):
    """Optimise la taille de l'image pour un upload rapide."""
    # Réduire légèrement la qualité pour économiser de l'espace
    buffer = BytesIO()
    
    # Convertir en mode RGB si nécessaire (supprimer canal alpha pour JPEG)
    if image.mode in ('RGBA', 'LA', 'P'):
        # Créer un fond blanc pour les images avec transparence
        fond = Image.new('RGB', image.size, (255, 255, 255))
        if image.mode == 'RGBA':
            fond.paste(image, mask=image.split()[3])  # 3ème canal = alpha
        else:
            fond.paste(image)
        image = fond
    
    # Sauvegarder avec optimisation
    image.save(buffer, format='JPEG', quality=qualite, optimize=True)
    buffer.seek(0)
    return buffer

def uploader_image_memory(image, nom_fichier, dossier):
    """Upload une image sur Cloudinary avec optimisation."""
    try:
        # Optimiser la taille avant upload
        buffer = optimiser_taille_image(image, qualite=85)
        
        # Upload directement depuis la mémoire
        response = cloudinary.uploader.upload(
            buffer,
            folder=dossier,
            public_id=nom_fichier,
            use_filename=True,
            unique_filename=False,
            overwrite=True,
            quality="auto:good",  # Optimisation automatique Cloudinary
            fetch_format="auto"   # Format optimal automatique
        )
        
        return response['secure_url']
        
    except Exception as e:
        print(f"❌ Erreur upload Cloudinary {nom_fichier}: {e}")
        return None

def sauvegarder_image_locale(image, nom_fichier, dossier):
    """Sauvegarde une image localement."""
    try:
        # Créer le dossier s'il n'existe pas
        dossier_complet = os.path.join("assets", "images", dossier)
        os.makedirs(dossier_complet, exist_ok=True)
        
        # Chemin complet
        chemin = os.path.join(dossier_complet, f"{nom_fichier}.jpg")
        
        # Sauvegarder (l'image est déjà un objet PIL)
        # Convertir en RGB si nécessaire
        if image.mode in ('RGBA', 'LA', 'P'):
            fond = Image.new('RGB', image.size, (255, 255, 255))
            if image.mode == 'RGBA':
                fond.paste(image, mask=image.split()[3])
            else:
                fond.paste(image)
            image = fond
            
        image.save(chemin, "JPEG", quality=85, optimize=True)
        
        # Retourner le chemin relatif pour le HTML
        return f"assets/images/{dossier}/{nom_fichier}.jpg"
        
    except Exception as e:
        print(f"❌ Erreur sauvegarde locale {nom_fichier}: {e}")
        return None

def main():
    print("🎨 GÉNÉRATION DES IMAGES CLOUDINARY (Version optimisée)")
    print("=" * 50)
    
    # Configuration
    # Configuration
    use_cloudinary = configurer_cloudinary()
    
    # Charger les données
    df = pd.read_csv('source.csv', dtype=str, keep_default_na=False)
    print(f"📊 {len(df)} grossistes à traiter")
    
    # Colonnes images
    if 'logo_url' not in df.columns:
        df['logo_url'] = ''
    if 'banner_url' not in df.columns:
        df['banner_url'] = ''
    
    # Traitement
    succes = 0
    echecs = 0
    
    for idx, row in df.iterrows():
        nom = row['nom_grossiste']
        if not nom or pd.isna(nom):
            continue
            
        # Nettoyer le nom pour le fichier
        nom_fichier = nom.lower().replace(' ', '-').replace('&', 'et')
        nom_fichier = ''.join(c for c in nom_fichier if c.isalnum() or c == '-')
        nom_fichier = re.sub(r'-+', '-', nom_fichier).strip('-')
        
        print(f"🔄 {idx+1}/{len(df)}: {nom}")
        
        try:
            # Générer images (tailles réduites)
            logo = generer_logo(nom, taille=(180, 180))  # Logo plus petit
            banniere = generer_banniere(nom, taille=(1000, 250))  # Bannière plus petite
            
            # Logique Hybride
            logo_url = None
            banner_url = None
            
            if use_cloudinary:
                # Tenter l'upload Cloudinary
                logo_url = uploader_image_memory(logo, f"{nom_fichier}-logo", "logos")
                banner_url = uploader_image_memory(banniere, f"{nom_fichier}-banner", "banners")
            
            # Si Cloudinary désactivé OU échoué -> Fallback Local
            if not logo_url:
                logo_url = sauvegarder_image_locale(logo, f"{nom_fichier}-logo", "logos")
                if use_cloudinary: print(f"   ⚠️ Bascule vers sauvegarde locale pour Logo")

            if not banner_url:
                banner_url = sauvegarder_image_locale(banniere, f"{nom_fichier}-banner", "banners")
                if use_cloudinary: print(f"   ⚠️ Bascule vers sauvegarde locale pour Bannière")
            
            if logo_url and banner_url:
                df.at[idx, 'logo_url'] = logo_url
                df.at[idx, 'banner_url'] = banner_url
                succes += 1
                type_svg = "☁️ Cloudinary" if "cloudinary" in str(logo_url) else "💾 Local"
                print(f"   ✅ Images générées ({type_svg})")
            else:
                # Fallback ultime (ne devrait pas arriver si le local marche)
                df.at[idx, 'logo_url'] = f"https://via.placeholder.com/180x180/36676a/FFFFFF?text={nom[:2].upper()}"
                df.at[idx, 'banner_url'] = f"https://via.placeholder.com/1000x250/2d5aa0/FFFFFF?text={nom}"
                echecs += 1
                print(f"   ❌ Echec total, utilisation placeholder")
                
        except Exception as e:
            print(f"   ❌ Erreur critique {nom}: {e}")
            echecs += 1
    
    # Sauvegarde
    df.to_csv('source.csv', index=False, encoding='utf-8')
    
    # Rapport final
    print(f"\n✅ TERMINÉ!")
    print(f"📊 RÉSULTATS:")
    print(f"   - Succès: {succes} grossistes avec images optimisées")
    print(f"   - Échecs: {echecs} grossistes avec URLs de fallback")
    print(f"   - Total: {len(df)} grossistes traités")
    print(f"\n💡 INFO:")
    print(f"   - Logo: 180x180px avec dégradé et initiales")
    print(f"   - Bannière: 1000x250px épurée, sans sous-titre")
    print(f"   - Format: JPEG optimisé pour taille mémoire réduite")

if __name__ == "__main__":
    main()